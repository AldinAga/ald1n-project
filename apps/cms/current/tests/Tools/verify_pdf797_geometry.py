from pathlib import Path
import fitz, json, re, sys
root=Path(sys.argv[1]); manifest=json.loads((root/'matrix.json').read_text())
issues=[];results=[];total_pages=0;total_spans=0
norm=lambda x: re.sub(r'\s+', '', x)
for fixture in manifest:
    path=root/fixture['file'];d=fitz.open(path);alltext='\n'.join(p.get_text() for p in d);fail=[]
    body=[]
    for page in d:
        header_bottom=max([x['rect'].y1 for x in page.get_drawings() if abs(x['rect'].x0)<.1 and abs(x['rect'].y0)<.1 and x['rect'].width>590] or [0])
        for b in page.get_text('dict')['blocks']:
            for line in b.get('lines',[]):
                for span in line['spans']:
                    if span['bbox'][1] >= header_bottom and span['bbox'][3] < 799 and span['text'] not in ('KUPAC', 'ARTIKAL', 'KUPAC (nastavak)', 'ARTIKAL (nastavak)'):
                        body.append(span['text'])
    bodytext=' '.join(body)
    if len(d)<fixture['minimum_pages']:fail.append('too_few_pages')
    if fixture['name'].endswith('-standard') and len(d)!=1:fail.append('ordinary_document_should_fit_one_page')
    for needle in fixture['needles']:
        if norm(needle) not in norm(alltext) and norm(needle) not in norm(bodytext):fail.append('missing_content:'+needle[:80])
    for pi,page in enumerate(d):
        if abs(page.rect.width-595)>1 or abs(page.rect.height-842)>1:fail.append(f'page{pi+1}:not_A4')
        for image in page.get_images():
            for rect in page.get_image_rects(image[0]):
                if image[2]>0 and image[3]>0 and abs(rect.width/rect.height-image[2]/image[3])>.01:fail.append(f'page{pi+1}:image_aspect_distorted')
        spans=[s for b in page.get_text('dict')['blocks'] if 'lines' in b for l in b['lines'] for s in l['spans'] if s['text'].strip()]
        total_spans+=len(spans)
        for si,s in enumerate(spans):
            b=fitz.Rect(s['bbox'])
            if b.x0<35.4 or b.x1>559.6 or b.y0<0 or b.y1>827:fail.append(f'page{pi+1}:bounds:{s["text"][:45]}:{list(b)}')
            for t in spans[si+1:]:
                c=b & fitz.Rect(t['bbox'])
                if not c.is_empty and c.width>1 and c.height>1:fail.append(f'page{pi+1}:overlap:{s["text"][:50]}:{t["text"][:50]}:{list(c)}')
        if f'Strana {pi+1}' not in page.get_text():fail.append(f'page{pi+1}:footer')
    if 'NE-PRIKAZUJ' in alltext:fail.append('privacy_email_leak')
    total_pages+=len(d)
    results.append({'name':fixture['name'],'pages':len(d),'issues':fail})
    issues.extend(fixture['name']+':'+x for x in fail)
report={'pdfs':len(manifest),'pages':total_pages,'text_spans':total_spans,'issue_count':len(issues),'results':results}
(root/'geometry.json').write_text(json.dumps(report,indent=2,ensure_ascii=False)+'\n')
print(json.dumps({k:v for k,v in report.items() if k!='results'}))
for x in issues[:25]:print(x)
sys.exit(1 if issues else 0)
