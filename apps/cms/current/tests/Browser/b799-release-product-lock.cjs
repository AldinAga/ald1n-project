'use strict';
module.exports=function releaseProductLock(stdin){
  if(!stdin.destroyed&&!stdin.writableEnded)stdin.end('RELEASE\n');
};
