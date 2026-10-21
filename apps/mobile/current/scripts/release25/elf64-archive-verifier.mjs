#!/usr/bin/env node
// Read-only Android 16 KB ELF gate. Requires Node >=22 and Info-ZIP unzip.
import { openSync, closeSync, readSync, fstatSync, lstatSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const PAGE=16384n, MAX=BigInt(Number.MAX_SAFE_INTEGER);
const LIMITS=Object.freeze({ archiveBytes:0xfffffffe, directoryBytes:16*1024*1024,
  listingBytes:8*1024*1024, memberBytes:128*1024*1024, totalNativeBytes:512*1024*1024,
  entries:50000, commandTimeoutMs:90000 });
const hex=n=>'0x'+n.toString(16);
function failure(code,message){const e=new Error(message);e.code=code;throw e;}
function requireThat(condition,code,message){if(!condition)failure(code,message);}

export function inspectElf64(buffer,abi) {
  const result={ok:false,abi,loads:[],relro:{status:'ABSENT',segments:[]},errors:[]};
  try {
    requireThat(Buffer.isBuffer(buffer)&&buffer.length>=64,'ELF_HEADER','Truncated ELF64 header');
    requireThat(buffer.subarray(0,4).equals(Buffer.from([127,69,76,70])),'ELF_MAGIC','Invalid ELF magic');
    requireThat(buffer[4]===2&&buffer[5]===1&&buffer[6]===1,'ELF_FORMAT','Require ELF64 little-endian version 1');
    const machine=abi==='arm64-v8a'?183:abi==='x86_64'?62:null;
    requireThat(machine!==null&&buffer.readUInt16LE(18)===machine,'ELF_ABI','ELF machine does not match archive ABI');
    requireThat(buffer.readUInt16LE(16)===3&&buffer.readUInt32LE(20)===1,'ELF_TYPE','Require ET_DYN ELF version 1');
    requireThat(buffer.readUInt16LE(52)===64&&buffer.readUInt16LE(54)===56,'ELF_HEADER_SIZE','Invalid ELF/program header sizes');
    const phoff=buffer.readBigUInt64LE(32),count=buffer.readUInt16LE(56);
    requireThat(count>0&&count!==65535,'ELF_PROGRAM_COUNT','Missing or unsupported extended program headers');
    requireThat(phoff>=64n&&phoff<=MAX&&phoff+BigInt(count)*56n<=BigInt(buffer.length),'ELF_PROGRAM_BOUNDS','Program headers outside file');
    for(let i=0;i<count;i++) {
      const at=Number(phoff)+i*56,type=buffer.readUInt32LE(at);
      if(type!==1&&type!==0x6474e552)continue;
      const offset=buffer.readBigUInt64LE(at+8),vaddr=buffer.readBigUInt64LE(at+16),
        filesz=buffer.readBigUInt64LE(at+32),memsz=buffer.readBigUInt64LE(at+40),align=buffer.readBigUInt64LE(at+48);
      requireThat([offset,vaddr,filesz,memsz,align].every(n=>n<=MAX)&&offset+filesz<=MAX&&vaddr+memsz<=MAX,
        'ELF_UNSAFE_INTEGER','Segment integer or endpoint exceeds safe range');
      requireThat(offset+filesz<=BigInt(buffer.length)&&filesz<=memsz,'ELF_SEGMENT_BOUNDS','Segment file bounds or memory size invalid');
      if(type===1) {
        const segment={index:i,offset:hex(offset),vaddr:hex(vaddr),fileSize:hex(filesz),memorySize:hex(memsz),align:hex(align)};
        result.loads.push(segment);
        if(align<PAGE||(align&(align-1n))!==0n)result.errors.push({code:'LOAD_ALIGNMENT',segment:i,message:'LOAD p_align must be a power of two >=0x4000'});
        else if(offset%align!==vaddr%align)result.errors.push({code:'LOAD_CONGRUENCE',segment:i,message:'LOAD p_offset and p_vaddr are not congruent modulo p_align'});
      } else {
        const end=vaddr+memsz,ok=end%PAGE===0n;
        result.relro.segments.push({index:i,vaddr:hex(vaddr),memorySize:hex(memsz),end:hex(end),ok});
        if(!ok)result.errors.push({code:'GNU_RELRO_ENDPOINT',segment:i,message:'GNU_RELRO (p_vaddr+p_memsz) is not 16KB aligned'});
      }
    }
    requireThat(result.loads.length>0,'ELF_NO_LOAD','No LOAD segments');
    if(result.relro.segments.length)result.relro.status=result.relro.segments.every(s=>s.ok)?'PASS':'FAIL';
    result.ok=result.errors.length===0;
  } catch(e) {result.errors.push({code:e.code??'ELF_PARSE',message:e.message});}
  return result;
}

function readAt(fd,length,position) {
  const b=Buffer.alloc(length);let read=0;
  while(read<length){const n=readSync(fd,b,read,length-read,position+read);requireThat(n>0,'ZIP_TRUNCATED','Truncated archive');read+=n;}
  return b;
}

// Metadata supplements unzip: reject duplicate names and Unix symlinks BEFORE
// extraction. ZIP64/multidisk are deliberately unsupported and fail closed.
function directory(fd,size) {
  const tail=readAt(fd,Math.min(size,65557),Math.max(0,size-65557));let end=-1;
  for(let i=tail.length-22;i>=0;i--)if(tail.readUInt32LE(i)===0x06054b50&&i+22+tail.readUInt16LE(i+20)===tail.length){end=i;break;}
  requireThat(end>=0,'ZIP_DIRECTORY','Missing end-of-central-directory');
  const disk=tail.readUInt16LE(end+4),cdDisk=tail.readUInt16LE(end+6),diskCount=tail.readUInt16LE(end+8),count=tail.readUInt16LE(end+10),
    cdBytes=tail.readUInt32LE(end+12),cdOffset=tail.readUInt32LE(end+16),endOffset=size-tail.length+end;
  requireThat(disk===0&&cdDisk===0&&diskCount===count&&count!==65535&&cdBytes!==0xffffffff&&cdOffset!==0xffffffff,
    'ZIP_UNSUPPORTED','ZIP64 or multidisk archive is unsupported');
  requireThat(count<=LIMITS.entries&&cdBytes<=LIMITS.directoryBytes&&cdOffset+cdBytes===endOffset,'ZIP_DIRECTORY_BOUNDS','Invalid/oversized central directory');
  const cd=readAt(fd,cdBytes,cdOffset),members=[],names=new Set();let at=0;
  for(let i=0;i<count;i++) {
    requireThat(at+46<=cd.length&&cd.readUInt32LE(at)===0x02014b50,'ZIP_ENTRY','Malformed central-directory entry');
    const flags=cd.readUInt16LE(at+8),method=cd.readUInt16LE(at+10),
      compressed=cd.readUInt32LE(at+20),size=cd.readUInt32LE(at+24),nameLength=cd.readUInt16LE(at+28),extra=cd.readUInt16LE(at+30),comment=cd.readUInt16LE(at+32),
      memberDisk=cd.readUInt16LE(at+34),attrs=cd.readUInt32LE(at+38),localOffset=cd.readUInt32LE(at+42),next=at+46+nameLength+extra+comment;
    requireThat(next<=cd.length&&nameLength>0,'ZIP_ENTRY_BOUNDS','Malformed entry lengths');
    const raw=cd.subarray(at+46,at+46+nameLength),name=raw.toString('utf8');
    requireThat(Buffer.from(name,'utf8').equals(raw)&&!/[\x00-\x1f\x7f\\*?\[\]]/.test(name)&&!name.startsWith('/')&&!name.split('/').some(p=>p==='.'||p==='..')&&!name.includes('//'),
      'ZIP_PATH','Unsafe or ambiguous ZIP member name');
    requireThat(!names.has(name),'DUPLICATE_ENTRY','Duplicate archive member: '+name);names.add(name);
    requireThat(((attrs>>>16)&0o170000)!==0o120000,'SYMLINK_ENTRY','Symlink archive member: '+name);
    requireThat(!(flags&1)&&!(flags&64)&&[0,8].includes(method)&&memberDisk===0&&compressed!==0xffffffff&&size!==0xffffffff&&localOffset<cdOffset,
      'ZIP_UNSUPPORTED_ENTRY','Encrypted, unsupported, or invalid member');
    members.push({name,size,compressed,localOffset});at=next;
  }
  requireThat(at===cd.length,'ZIP_DIRECTORY_TRAILING','Unparsed central-directory bytes');
  return members;
}

function unzip(args,maxBuffer) {
  const child=spawnSync('unzip',args,{maxBuffer,timeout:LIMITS.commandTimeoutMs,windowsHide:true,env:{...process.env,LC_ALL:'C',UNZIP:'',UNZIPOPT:'',ZIPINFO:'',ZIPINFOOPT:''}});
  requireThat(!child.error&&child.status===0,'UNZIP_FAILED','unzip failed, exceeded buffer/timeout, or found invalid ZIP data');
  return child.stdout;
}

export function inspectArchive(filename) {
  const result={schemaVersion:1,ok:false,archive:path.resolve(filename),pageSize:16384,checkedCount:0,entries:[],errors:[],limits:LIMITS};
  let fd;
  try {
    requireThat(!lstatSync(filename).isSymbolicLink(),'ARCHIVE_SYMLINK','Archive input must not be a symlink');
    fd=openSync(filename,'r');const before=fstatSync(fd);
    requireThat(before.isFile()&&before.size>=22&&before.size<=LIMITS.archiveBytes,'ARCHIVE_SIZE','Archive is not a regular supported-size file');
    const members=directory(fd,before.size),absolute=path.resolve(filename);
    const listed=unzip(['-Z1',absolute],LIMITS.listingBytes).toString('utf8').split('\n');
    if(listed.at(-1)==='')listed.pop();
    requireThat(listed.length===members.length&&listed.every((v,i)=>v===members[i].name),'ZIP_LISTING_MISMATCH','unzip listing differs from central directory');
    const selected=[];
    for(const m of members) {
      const match=/^(?:base\/lib|lib|jni)\/(arm64-v8a|x86_64)\/([^/]+\.so)$/.exec(m.name);
      if(match)selected.push({...m,abi:match[1]});
      else requireThat(!/(?:^|\/)(?:arm64-v8a|x86_64)\/.*\.so$/.test(m.name),'UNSUPPORTED_NATIVE_PATH','64-bit native library outside supported flat archive paths');
    }
    requireThat(selected.length>0,'NO_64BIT_LIBRARIES','No arm64-v8a/x86_64 shared libraries found');
    requireThat(selected.every(m=>m.size>0&&m.size<=LIMITS.memberBytes)&&selected.reduce((n,m)=>n+m.size,0)<=LIMITS.totalNativeBytes,
      'NATIVE_SIZE_LIMIT','Native extraction size limit exceeded');
    unzip(['-tqq',absolute],1024*1024);
    selected.sort((a,b)=>a.name<b.name?-1:a.name>b.name?1:0);
    for(const m of selected) {
      const bytes=unzip(['-p',absolute,m.name],LIMITS.memberBytes);
      requireThat(bytes.length===m.size,'ZIP_MEMBER_SIZE','Extracted size differs from central directory');
      const inspected=inspectElf64(bytes,m.abi);
      result.entries.push({path:m.name,bytes:bytes.length,...inspected});
      if(!inspected.ok)result.errors.push({code:'ELF_INVALID',path:m.name,message:'Native ELF validation failed'});
    }
    const after=fstatSync(fd),byPath=lstatSync(filename);
    requireThat(before.dev===after.dev&&before.ino===after.ino&&before.size===after.size&&before.mtimeMs===after.mtimeMs&&before.ctimeMs===after.ctimeMs&&after.ino===byPath.ino&&after.dev===byPath.dev,
      'ARCHIVE_CHANGED','Archive changed during inspection');
    result.checkedCount=result.entries.length;result.ok=result.errors.length===0;
  }catch(e){result.errors.push({code:e.code??'ARCHIVE_READ',message:e.message});}
  finally{result.checkedCount=result.entries.length;if(fd!==undefined)closeSync(fd);}
  return result;
}

if(process.argv[1]&&path.resolve(process.argv[1])===fileURLToPath(import.meta.url)) {
  const result=process.argv.length===3?inspectArchive(process.argv[2]):{ok:false,errors:[{code:'USAGE',message:'Usage: node elf64-archive-verifier.mjs /absolute/path/app.aab|app.apk|vendor.aar'}]};
  process.stdout.write(JSON.stringify(result,(_key,value)=>typeof value==='bigint'?value.toString():value,2)+'\n');
  process.exitCode=result.ok?0:1;
}
