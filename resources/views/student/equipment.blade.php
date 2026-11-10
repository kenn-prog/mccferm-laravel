@extends('layouts.app')
@section('content')
@php $cats = $items->pluck('category')->unique()->values(); $total=$items->sum('total_quantity'); $avail=$items->sum('available_quantity'); @endphp
<div class="head"><div><div class="eyebrow">Inventory</div><h1>Equipment</h1><p class="sub">Check availability and request to borrow.</p></div><button class="btn" type="button" data-toggle-target="borrow-dialog" aria-haspopup="dialog" aria-expanded="false" aria-controls="borrow-dialog">+ Borrow equipment</button></div>
<div class="chips"><button type="button" class="chip on" data-filter="all">All</button>@foreach($cats as $c)<button type="button" class="chip" data-filter="{{ strtolower($c) }}">{{ $c }}</button>@endforeach</div>
<div class="split">
  <div class="fgrid" style="margin:0;grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
    @foreach($items as $e)
      @php $ok = $e->available_quantity>0 && $e->status==='available'; $g = strtolower($e->category); @endphp
      <div class="ecard" data-cat="{{ $g }}">
        <div class="eimg g-{{ in_array($g,['av','sports','furniture']) ? $g : 'def' }}"><svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg></div>
        <div class="fbody"><b>{{ $e->name }}</b><small>{{ $e->category }} · {{ $e->available_quantity }} of {{ $e->total_quantity }} available</small>
          <div class="fmeta"><span class="badge {{ $ok ? 'available' : 'unavailable' }}">{{ $ok ? 'Available' : 'Unavailable' }}</span>
            @if($ok)<button type="button" class="btn s" data-open-selected-dialog aria-haspopup="dialog" aria-expanded="false" aria-controls="borrow-dialog" onclick="pick('equipment_id','{{ $e->id }}','borrow-dialog')">Borrow</button>@endif</div></div>
      </div>
    @endforeach
  </div>
  <div class="card"><h3>Equipment Summary</h3>
    <div class="item"><div><b>{{ $total }}</b><small>Total items</small></div></div>
    <div class="item"><div><b>{{ $avail }}</b><small>Available</small></div></div>
    <div class="item"><div><b>{{ $total-$avail }}</b><small>Borrowed</small></div></div>
  </div>
</div>
<dialog class="card add-form" id="borrow-dialog">
  <div class="add-form-header"><h3>Borrowing request</h3><button class="btn s l" type="button" data-close-dialog>Cancel</button></div>
  <form method="post" action="/borrow">@csrf
    <div class="row">
      <div><label>Equipment</label><select name="equipment_id">@foreach($items->where('available_quantity','>',0) as $e)<option value="{{ $e->id }}">{{ $e->name }} ({{ $e->available_quantity }})</option>@endforeach</select></div>
      <div><label>Quantity</label><input type="number" name="quantity" min="1" value="1"></div>
      <div><label>Borrow date</label><input type="date" name="borrow_date" min="{{ today()->toDateString() }}" value="{{ old('borrow_date') }}" required></div>
      <div><label>Expected return</label><input type="date" name="expected_return" min="{{ old('borrow_date') }}" max="{{ old('borrow_date') ? \Illuminate\Support\Carbon::parse(old('borrow_date'))->addDays(5)->toDateString() : '' }}" value="{{ old('expected_return') }}" required><small style="display:block;color:var(--mute);margin-top:-8px">Maximum 5 days after the borrow date.</small></div>
    </div>
    <label>Purpose</label><input name="purpose" required>
    <button class="btn">Submit request</button>
  </form>
</dialog>
@if($errors->any())<script>document.getElementById('borrow-dialog').showModal();</script>@endif
<script>
const borrowDateInput = document.querySelector('#borrow-dialog [name="borrow_date"]');
const expectedReturnInput = document.querySelector('#borrow-dialog [name="expected_return"]');
function updateReturnDateLimits() {
  const borrowDate = borrowDateInput.value;
  expectedReturnInput.min = borrowDate;
  if (!borrowDate) {
    expectedReturnInput.removeAttribute('max');
    return;
  }
  const latestDate = new Date(borrowDate + 'T00:00:00');
  latestDate.setDate(latestDate.getDate() + 5);
  expectedReturnInput.max = [
    latestDate.getFullYear(),
    String(latestDate.getMonth() + 1).padStart(2, '0'),
    String(latestDate.getDate()).padStart(2, '0')
  ].join('-');
}
borrowDateInput.addEventListener('change', updateReturnDateLimits);
updateReturnDateLimits();
</script>
@endsection
