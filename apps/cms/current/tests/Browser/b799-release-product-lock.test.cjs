'use strict';
const test=require('node:test');
const assert=require('node:assert/strict');
const {PassThrough}=require('node:stream');
const {finished}=require('node:stream/promises');
const releaseProductLock=require('./b799-release-product-lock.cjs');

test('normal release followed by finally cleanup sends the lock release exactly once',async()=>{
  const stdin=new PassThrough();
  let output='';
  stdin.setEncoding('utf8');
  stdin.on('data',chunk=>{output+=chunk});
  releaseProductLock(stdin);
  assert.equal(stdin.writableEnded,true);
  assert.equal(stdin.destroyed,false);
  releaseProductLock(stdin);
  await finished(stdin);
  assert.equal(output,'RELEASE\n');
});

test('cleanup does not write to a destroyed lock pipe',async()=>{
  const stdin=new PassThrough();
  stdin.resume();
  stdin.destroy();
  releaseProductLock(stdin);
  await assert.rejects(finished(stdin),{code:'ERR_STREAM_PREMATURE_CLOSE'});
  assert.equal(stdin.writableEnded,false);
  assert.equal(stdin.writableLength,0);
});
