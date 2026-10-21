import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mkdtempSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { inspectElf64, inspectArchive } from './elf64-archive-verifier.mjs';

function elf({ align=0x4000n, relro=true, relroSize=0x4000n, machine=183 }={}) {
  const b=Buffer.alloc(0x4000); b.set([0x7f,69,76,70,2,1,1]);
  b.writeUInt16LE(3,16); b.writeUInt16LE(machine,18); b.writeUInt32LE(1,20);
  b.writeBigUInt64LE(64n,32); b.writeUInt16LE(64,52); b.writeUInt16LE(56,54); b.writeUInt16LE(relro?2:1,56);
  b.writeUInt32LE(1,64); b.writeUInt32LE(5,68); b.writeBigUInt64LE(0n,72); b.writeBigUInt64LE(0n,80);
  b.writeBigUInt64LE(0x4000n,96); b.writeBigUInt64LE(0x4000n,104); b.writeBigUInt64LE(align,112);
  if(relro){ b.writeUInt32LE(0x6474e552,120); b.writeBigUInt64LE(0n,128); b.writeBigUInt64LE(0n,136); b.writeBigUInt64LE(relroSize,152); b.writeBigUInt64LE(relroSize,160); b.writeBigUInt64LE(1n,168); }
  return b;
}
function crc32(b){let n=0xffffffff;for(const v of b){n^=v;for(let i=0;i<8;i++)n=(n>>>1)^((n&1)?0xedb88320:0);}return(n^0xffffffff)>>>0;}
// Actual ZIP bytes exercise unzip, duplicate names, CRC and Unix mode handling.
function zip(entries){let offset=0;const locals=[],central=[];for(const e of entries){const name=Buffer.from(e.name),data=e.data??elf();const c=crc32(data);const h=Buffer.alloc(30);h.writeUInt32LE(0x04034b50);h.writeUInt16LE(20,4);h.writeUInt32LE(c,14);h.writeUInt32LE(data.length,18);h.writeUInt32LE(data.length,22);h.writeUInt16LE(name.length,26);locals.push(h,name,data);const d=Buffer.alloc(46);d.writeUInt32LE(0x02014b50);d.writeUInt16LE(0x0314,4);d.writeUInt16LE(20,6);d.writeUInt32LE(c,16);d.writeUInt32LE(data.length,20);d.writeUInt32LE(data.length,24);d.writeUInt16LE(name.length,28);d.writeUInt32LE(((e.mode??0o100644)*65536)>>>0,38);d.writeUInt32LE(offset,42);central.push(d,name);offset+=h.length+name.length+data.length;}const cd=Buffer.concat(central),end=Buffer.alloc(22);end.writeUInt32LE(0x06054b50);end.writeUInt16LE(entries.length,8);end.writeUInt16LE(entries.length,10);end.writeUInt32LE(cd.length,12);end.writeUInt32LE(offset,16);return Buffer.concat([...locals,cd,end]);}
function archive(t,entries){const dir=mkdtempSync(path.join(tmpdir(),'elf64-test-'));t.after(()=>rmSync(dir,{recursive:true,force:true}));const f=path.join(dir,'fixture.zip');writeFileSync(f,zip(entries));return f;}
const good='base/lib/arm64-v8a/libnativegauss.so';
test('aligned LOAD and RELRO pass with their actual values',()=>{const r=inspectElf64(elf(),'arm64-v8a');assert.equal(r.ok,true);assert.equal(r.relro.status,'PASS');assert.equal(r.loads[0].align,'0x4000');});
test('4KB LOAD fails even if RELRO ends on a 16KB boundary',()=>{const r=inspectElf64(elf({align:4096n}),'arm64-v8a');assert.equal(r.ok,false);assert.ok(r.errors.some(x=>x.code==='LOAD_ALIGNMENT'));});
test('aligned LOAD with 4KB RELRO endpoint fails',()=>{const r=inspectElf64(elf({relroSize:4096n}),'arm64-v8a');assert.equal(r.ok,false);assert.equal(r.relro.status,'FAIL');});
test('missing RELRO is distinct and allowed',()=>{const r=inspectElf64(elf({relro:false}),'arm64-v8a');assert.equal(r.ok,true);assert.equal(r.relro.status,'ABSENT');});
test('LOAD address/offset must be congruent',()=>{const b=elf();b.writeBigUInt64LE(1n,80);assert.equal(inspectElf64(b,'arm64-v8a').ok,false);});
test('ABI machine mismatch fails',()=>assert.equal(inspectElf64(elf({machine:62}),'arm64-v8a').ok,false));
test('truncated headers and program table fail closed',()=>{assert.equal(inspectElf64(Buffer.alloc(20),'arm64-v8a').ok,false);const b=elf();b.writeBigUInt64LE(0x3fffn,32);assert.equal(inspectElf64(b,'arm64-v8a').ok,false);});
test('unsafe offsets and overflowing virtual ranges fail closed',()=>{const b=elf();b.writeBigUInt64LE(0xffffffffffffffffn,72);assert.equal(inspectElf64(b,'arm64-v8a').ok,false);const c=elf();c.writeBigUInt64LE(0xffffffffffffffffn,136);assert.equal(inspectElf64(c,'arm64-v8a').ok,false);});
test('wrong class, endian, type, PH size and missing LOAD fail closed',()=>{for(const change of [b=>b[4]=1,b=>b[5]=2,b=>b.writeUInt16LE(2,16),b=>b.writeUInt16LE(55,54),b=>b.writeUInt32LE(0,64)]){const b=elf();change(b);assert.equal(inspectElf64(b,'arm64-v8a').ok,false);}});
test('all64bit archive entries are checked in sorted order',t=>{const f=archive(t,[{name:'lib/x86_64/libz.so',data:elf({machine:62})},{name:good},{name:'lib/armeabi-v7a/ignored.so',data:Buffer.from('32bit')}]);const r=inspectArchive(f);assert.equal(r.ok,true);assert.equal(r.checkedCount,2);assert.deepEqual(r.entries.map(x=>x.path),[good,'lib/x86_64/libz.so']);});
test('one invalid member makes the whole archive fail',t=>{const r=inspectArchive(archive(t,[{name:good},{name:'jni/x86_64/bad.so',data:elf({machine:62,relroSize:4096n})}]));assert.equal(r.ok,false);assert.equal(r.checkedCount,2);});
test('duplicate archive names are rejected before extraction',t=>{const r=inspectArchive(archive(t,[{name:good},{name:good}]));assert.equal(r.ok,false);assert.ok(r.errors.some(x=>x.code==='DUPLICATE_ENTRY'));});
test('symlink native library is rejected',t=>{const r=inspectArchive(archive(t,[{name:good,mode:0o120777,data:Buffer.from('elsewhere')}]));assert.equal(r.ok,false);assert.ok(r.errors.some(x=>x.code==='SYMLINK_ENTRY'));});
test('archive with no64bit libraries is rejected',t=>assert.equal(inspectArchive(archive(t,[{name:'readme.txt',data:Buffer.from('text')}])).ok,false));
test('corrupt CRC and malformed central directory are rejected',t=>{const f=archive(t,[{name:good}]);const b=zip([{name:good}]);b[30+Buffer.byteLength(good)+200]^=1;writeFileSync(f,b);assert.equal(inspectArchive(f).ok,false);writeFileSync(f,Buffer.from('notzip'));assert.equal(inspectArchive(f).ok,false);});
test('unsafe paths, extraction globs and unsupported64bit paths are rejected',t=>{for(const name of ['../'+good,'lib/arm64-v8a/a[1].so','feature/lib/arm64-v8a/a.so'])assert.equal(inspectArchive(archive(t,[{name}])).ok,false);});
test('CLI returns machine-readable JSON and nonzero for invalid input',t=>{const f=archive(t,[{name:good,data:elf({align:4096n})}]);const r=spawnSync(process.execPath,[fileURLToPath(new URL('./elf64-archive-verifier.mjs',import.meta.url)),f],{encoding:'utf8'});assert.equal(r.status,1);assert.equal(JSON.parse(r.stdout).ok,false);});

function dedicatedRelro(nextWritable=0x8000n){
 const b=Buffer.alloc(0x10000);b.set(elf().subarray(0,64));b.writeUInt16LE(4,56);
 const header=(i,type,flags,offset,vaddr,filesz,memsz,align)=>{const p=64+i*56;b.writeUInt32LE(type,p);b.writeUInt32LE(flags,p+4);for(const [at,n] of [[8,offset],[16,vaddr],[32,filesz],[40,memsz],[48,align]])b.writeBigUInt64LE(n,p+at);};
 header(0,1,5,0n,0n,0x680n,0x680n,0x4000n);
 header(1,1,6,0x680n,0x4680n,0x1d8n,0x980n,0x4000n);
 header(2,0x6474e552,4,0x680n,0x4680n,0x1d8n,0x980n,1n);
 header(3,1,6,nextWritable%0x4000n,nextWritable,0n,0x100n,0x4000n);
 return b;
}
test('official dedicated RELRO LOAD passes despite unaligned endpoint',()=>{
 const r=inspectElf64(dedicatedRelro(),'arm64-v8a');assert.equal(r.ok,true,JSON.stringify(r.errors));
 assert.equal(r.relro.status,'PASS');assert.equal(r.relro.segments[0].reason,'DEDICATED_LOAD');assert.equal(r.relro.segments[0].containingLoadIndex,1);
});
test('dedicated RELRO rejects another writable LOAD inside rounded protection page',()=>{
 const r=inspectElf64(dedicatedRelro(0x6000n),'arm64-v8a');assert.equal(r.ok,false);assert.equal(r.relro.status,'FAIL');
 assert.ok(r.errors.some(e=>e.code==='GNU_RELRO_WRITABLE_OVERLAP'));
});
test('unaligned RELRO sharing a LOAD with writable tail still fails',()=>{
 const b=dedicatedRelro();b.writeBigUInt64LE(0x1980n,64+56+40);
 assert.equal(inspectElf64(b,'arm64-v8a').ok,false);
});
test('aligned RELRO may have FileSiz greater than MemSiz when file bounds are valid',()=>{
 const b=Buffer.alloc(0x6000);b.set(elf());b.writeBigUInt64LE(0x6000n,96);b.writeBigUInt64LE(0x6000n,104);
 b.writeBigUInt64LE(0x3000n,128);b.writeBigUInt64LE(0x3000n,136);b.writeBigUInt64LE(0x2000n,152);b.writeBigUInt64LE(0x1000n,160);
 const r=inspectElf64(b,'arm64-v8a');assert.equal(r.ok,true,JSON.stringify(r.errors));
});
test('RELRO outside all LOAD memory ranges fails even when endpoint aligned',()=>{
 const b=elf();b.writeBigUInt64LE(0x8000n,136);const r=inspectElf64(b,'arm64-v8a');assert.equal(r.ok,false);
 assert.ok(r.errors.some(e=>e.code==='GNU_RELRO_NOT_CONTAINED'));
});
test('dedicated RELRO rejects preceding writable data on its rounded start page',()=>{
 const r=inspectElf64(dedicatedRelro(0x4000n),'arm64-v8a');assert.equal(r.ok,false);
 assert.ok(r.errors.some(e=>e.code==='GNU_RELRO_WRITABLE_OVERLAP'));
});
test('other read-only or empty writable LOAD does not create an overlap failure',()=>{
 const readOnly=dedicatedRelro(0x6000n);readOnly.writeUInt32LE(4,64+3*56+4);
 assert.equal(inspectElf64(readOnly,'arm64-v8a').ok,true);
 const empty=dedicatedRelro(0x6000n);empty.writeBigUInt64LE(0n,64+3*56+40);
 assert.equal(inspectElf64(empty,'arm64-v8a').ok,true);
});
test('dedicated exception retains LOAD size and RELRO file bounds checks',()=>{
 const load=dedicatedRelro();load.writeBigUInt64LE(0x100n,64+56+40);
 assert.ok(inspectElf64(load,'arm64-v8a').errors.some(e=>e.code==='ELF_SEGMENT_BOUNDS'));
 const relro=dedicatedRelro();relro.writeBigUInt64LE(0x10000n,64+2*56+32);
 assert.ok(inspectElf64(relro,'arm64-v8a').errors.some(e=>e.code==='ELF_SEGMENT_BOUNDS'));
});
test('aligned RELRO end allows linker padding up to containing LOAD mapped page end',()=>{
 const b=dedicatedRelro();b.writeBigUInt64LE(0x3980n,64+2*56+40);
 const r=inspectElf64(b,'arm64-v8a');assert.equal(r.ok,true,JSON.stringify(r.errors));assert.equal(r.relro.segments[0].reason,'END_ALIGNED');
});
