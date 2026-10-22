from pathlib import Path
import base64, hashlib, lzma, os, subprocess

def apply(prefix, count, expected, path_count):
    encoded=''.join(Path(f'qa/b797-candidate/{prefix}{i:02d}.b64').read_text().strip() for i in range(count))
    patch=lzma.decompress(base64.b64decode(encoded,validate=True))
    assert hashlib.sha256(patch).hexdigest()==expected, f'{prefix}: integrity mismatch'
    paths=[line.split()[2][2:] for line in patch.decode().splitlines() if line.startswith('diff --git ')]
    assert len(paths)==path_count and all(p.startswith('apps/cms/current/') and '..' not in Path(p).parts for p in paths)
    path=Path(os.environ['RUNNER_TEMP'])/f'b797-{prefix}.patch';path.write_bytes(patch)
    subprocess.run(['git','apply','--check',str(path)],check=True)
    subprocess.run(['git','apply',str(path)],check=True)

subprocess.run(['git','diff','--exit-code','9bf18d475cd603783c7c81c6a2001875aed3ccee','HEAD','--',
    'apps/cms/current/app','apps/cms/current/routes','apps/cms/current/database','apps/cms/current/resources','apps/cms/current/composer.lock'],check=True)
apply('part',5,'b912d1009f61a1d2d5c71821de73b1b35b66d52efba7c2835134e37f22143185',28)
subprocess.run(['python','qa/b797-candidate/fix-fixtures.py'],check=True)
apply('safety',2,'0aea4483663909544042a75ae172d22ced45d98cbf494ce1f590703a1e239fd5',8)
subprocess.run(['git','diff','--check'],check=True)
print('EXACT_B797_CANDIDATE_ASSEMBLY=PASS')
