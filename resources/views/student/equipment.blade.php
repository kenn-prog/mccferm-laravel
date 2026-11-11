@extends('layouts.app')
@section('content')
@php $cats = $items->pluck('category')->unique()->values(); $total=$items->sum('total_quantity'); $avail=$items->sum('available_quantity'); $borrowableItems=$items->where('status','available')->where('available_quantity','>',0); $selectedBorrowableItem=$borrowableItems->firstWhere('id',old('equipment_id')) ?? $borrowableItems->first(); @endphp
<div class="page-header">
  <div class="page-header-content">
    <div class="eyebrow">Inventory</div>
    <h1>Equipment</h1>
    <p class="sub">Check availability and request to borrow.</p>
  </div>
  <button class="btn btn-icon" type="button" data-toggle-target="borrow-dialog" aria-haspopup="dialog" aria-expanded="false" aria-controls="borrow-dialog">
    <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
    Borrow equipment
  </button>
</div>
<div class="chips"><button type="button" class="chip on" data-filter="all">All</button>@foreach($cats as $c)<button type="button" class="chip" data-filter="{{ strtolower($c) }}">{{ $c }}</button>@endforeach</div>
<div class="split">
  <div class="equipment-grid">
    @foreach($items as $e)
      @php $ok = $e->available_quantity>0 && $e->status==='available'; $g = strtolower($e->category); $percent = $e->total_quantity > 0 ? round(($e->available_quantity / $e->total_quantity) * 100) : 0; @endphp
      <div class="equipment-card-enhanced" data-cat="{{ $g }}">
        <div class="equipment-card-header">
          <div class="equipment-icon g-{{ in_array($g,['av','sports','furniture']) ? $g : 'def' }}">
            <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
          </div>
          <div class="equipment-status-badge">
            <span class="badge {{ $ok ? 'available' : 'unavailable' }}">
              <svg viewBox="0 0 24 24" style="width:12px;height:12px">
                @if($ok)<path d="M5 13l4 4L19 7"/>@else<path d="M18 6L6 18M6 6l12 12"/>@endif
              </svg>
              {{ $ok ? 'Available' : 'Unavailable' }}
            </span>
          </div>
        </div>
        <div class="equipment-card-body">
          <div class="equipment-card-title">{{ $e->name }}</div>
          <div class="equipment-card-meta">
            <div class="equipment-category">
              <svg viewBox="0 0 24 24"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>
              {{ $e->category }}
            </div>
            @if($e->condition && $e->condition !== 'Good')
            <div class="equipment-condition" title="Condition: {{ $e->condition }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              {{ $e->condition }}
            </div>
            @endif
          </div>
          <div class="equipment-availability">
            <div class="availability-header">
              <span class="availability-label">Availability</span>
              <span class="availability-count">
                <strong>{{ $e->available_quantity }}</strong> / {{ $e->total_quantity }}
              </span>
            </div>
            <div class="availability-bar">
              <div class="availability-progress" style="width:{{ $percent }}%"></div>
            </div>
            <div class="availability-text">
              {{ $e->available_quantity }} unit{{ $e->available_quantity != 1 ? 's' : '' }} available
            </div>
          </div>
          @if($ok)
          <button type="button" class="btn-equipment-borrow" data-open-selected-dialog aria-haspopup="dialog" aria-expanded="false" aria-controls="borrow-dialog" onclick="pick('equipment_id','{{ $e->id }}','borrow-dialog'); updateBorrowQuantityLimit()">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            Request to Borrow
          </button>
          @else
          <div class="equipment-unavailable-notice">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>Currently unavailable for borrowing</span>
          </div>
          @endif
        </div>
      </div>
    @endforeach
  </div>
  <div class="enhanced-card">
    <div class="cardhead"><h3>Equipment Summary</h3></div>
    <div class="enhanced-item">
      <div class="item-icon">
        <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/></svg>
      </div>
      <div class="item-content">
        <div class="item-title">{{ $total }}</div>
        <div class="item-subtitle">Total items</div>
      </div>
    </div>
    <div class="enhanced-item">
      <div class="item-icon">
        <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
      </div>
      <div class="item-content">
        <div class="item-title">{{ $avail }}</div>
        <div class="item-subtitle">Available</div>
      </div>
    </div>
    <div class="enhanced-item">
      <div class="item-icon">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      </div>
      <div class="item-content">
        <div class="item-title">{{ $total-$avail }}</div>
        <div class="item-subtitle">Borrowed</div>
      </div>
    </div>
  </div>
</div>
<dialog class="card add-form" id="borrow-dialog">
  <div class="add-form-header"><h3>Borrowing request</h3><button class="btn s l" type="button" data-close-dialog>Cancel</button></div>
  <form method="post" action="{{ request()->boolean('request_followup') ? '/borrow?request_followup=1' : '/borrow' }}">@csrf
    <div class="row">
      <div><label for="borrow-equipment">Equipment</label><select id="borrow-equipment" name="equipment_id" required>@forelse($borrowableItems as $e)<option value="{{ $e->id }}" data-available="{{ $e->available_quantity }}" @selected(old('equipment_id')==$e->id)>{{ $e->name }}</option>@empty<option value="" disabled selected>No equipment currently available</option>@endforelse</select></div>
      <div><label for="borrow-quantity" id="borrow-quantity-label">Quantity ({{ $selectedBorrowableItem?->available_quantity ?? 0 }} available)</label><input id="borrow-quantity" type="number" name="quantity" min="1" max="{{ $selectedBorrowableItem?->available_quantity ?? 0 }}" value="{{ old('quantity',1) }}" required></div>
      <div><label>Borrow date</label><input type="date" name="borrow_date" data-school-open-date min="{{ today()->toDateString() }}" value="{{ old('borrow_date') }}" required><small>School is closed on Sundays.</small></div>
      <div><label>Pickup time (8:00 AM–8:00 PM)</label><input type="time" name="borrow_time" min="08:00" max="20:00" value="{{ old('borrow_time') }}" required></div>
      <div><label>Expected return</label><input type="date" name="expected_return" data-school-open-date min="{{ old('borrow_date') }}" max="{{ old('borrow_date') ? \Illuminate\Support\Carbon::parse(old('borrow_date'))->addDays(5)->toDateString() : '' }}" value="{{ old('expected_return') }}" required><small style="display:block;color:var(--mute);margin-top:-8px">Maximum 5 days after the borrow date. School is closed on Sundays.</small></div>
      <div><label>Return time (8:00 AM–8:00 PM)</label><input type="time" name="expected_return_time" min="08:00" max="20:00" value="{{ old('expected_return_time') }}" required></div>
    </div>
    <label>Purpose</label><input name="purpose" required>
    <button class="btn">Submit request</button>
  </form>
</dialog>
@if($errors->any())<script>document.getElementById('borrow-dialog').showModal();</script>@endif
<script>
const borrowEquipmentInput = document.getElementById('borrow-equipment');
const borrowQuantityInput = document.getElementById('borrow-quantity');
const borrowQuantityLabel = document.getElementById('borrow-quantity-label');
function updateBorrowQuantityLimit() {
  const available = Number(borrowEquipmentInput.selectedOptions[0]?.dataset.available || 0);
  borrowQuantityInput.max = available;
  borrowQuantityLabel.textContent = 'Quantity (' + available + ' available)';
  if (Number(borrowQuantityInput.value) > available) borrowQuantityInput.value = String(available);
}
borrowEquipmentInput.addEventListener('change', updateBorrowQuantityLimit);
updateBorrowQuantityLimit();
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
