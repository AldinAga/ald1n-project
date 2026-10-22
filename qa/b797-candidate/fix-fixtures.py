from pathlib import Path
root=Path('apps/cms/current/tests/Feature')
p=root/'FirstPaymentWarranty797Test.php'; s=p.read_text()
old="$this->pay797($order,$admin,1,'refund'); $this->pay797($order,$admin,0);"
new="""// A refund needs an existing positive balance; do not exercise the unrelated legacy refunded-ENUM defect.
        WarrantyRule::query()->update(['is_active'=>false]); $this->pay797($order,$admin,5);
        WarrantyRule::query()->update(['is_active'=>true]);
        $this->pay797($order,$admin,1,'refund'); $this->pay797($order,$admin,0);"""
assert s.count(old)==1;s=s.replace(old,new)
assert s.count('$before=$existing->toArray();')==1;s=s.replace('$before=$existing->toArray();','$before=$existing->fresh()->toArray();')
p.write_text(s)
p=root/'WarrantyExpiryIsolation797Test.php';s=p.read_text()
old="[$otherOrder]=$this->order797('mismatch'); $warranty->update(['order_id'=>$otherOrder->id]);"
assert s.count(old)==1;s=s.replace(old,old+'\n        Notification::fake(); // Exclude unrelated order-created fixture notifications.')
p.write_text(s)
