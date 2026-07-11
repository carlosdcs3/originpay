<div class="row g-3 mt-1 personal-fields">
	<div class="col-md-6">
		<label for="first_name" class="v2-label">@lang('First Name')</label>
		<input type="text" class="v2-input" id="first_name" name="first_name" value="{{ old('first_name', $cardholder->first_name ?? '') }}" placeholder="@lang('Enter first name')">
	</div>
	<div class="col-md-6">
		<label for="last_name" class="v2-label">@lang('Last Name')</label>
		<input type="text" class="v2-input" id="last_name" name="last_name" value="{{ old('last_name', $cardholder->last_name ?? '') }}" placeholder="@lang('Enter last name')">
	</div>
	<div class="col-md-6">
		<label for="email" class="v2-label">@lang('Email')</label>
		<input type="email" class="v2-input" id="email" name="email" value="{{ old('email', $cardholder->email ?? '') }}" placeholder="@lang('Enter email address')">
	</div>
	<div class="col-md-6">
		<label for="mobile" class="v2-label">@lang('Mobile')</label>
		<input type="text" class="v2-input" id="mobile" name="mobile" value="{{ old('mobile', $cardholder->mobile ?? $cardholder->phone ?? '') }}" placeholder="@lang('Enter mobile number')">
	</div>
	<div class="col-md-6">
		<label for="gender" class="v2-label">@lang('Gender')</label>
		<select class="v2-input" id="gender" name="gender">
			<option value="">@lang('Select Gender')</option>
			@foreach ($genderOptions as $value => $label)
				<option value="{{ $value }}" @selected(old('gender', $cardholder->gender->value ?? '') == $value)>{{ $label }}</option>
			@endforeach
		</select>
	</div>

	
	<div class="col-md-6">
		<label for="dob" class="v2-label">@lang('Date of Birth')</label>
		<input type="date" class="v2-input" id="dob" name="dob"
		       value="{{ old('dob', isset($cardholder) && $cardholder?->dob ? $cardholder->dob->format('Y-m-d') : '') }}"
		       placeholder="@lang('YYYY-MM-DD')">
	</div>
	<div class="col-md-6">
		<label for="relation" class="v2-label">@lang('Relation')</label>
		<input type="text" class="v2-input" id="relation" name="relation" value="{{ old('relation', $cardholder->relation ?? '') }}" placeholder="@lang('Enter relation (optional)')">
	</div>
	<div class="col-md-12">
		<label for="address_line1" class="v2-label">@lang('Address Line 1')</label>
		<input type="text" class="v2-input" id="address_line1" name="address_line1" value="{{ old('address_line1', $cardholder->address_line1 ?? '') }}" placeholder="@lang('Street address, P.O. box, company name, c/o')">
	</div>
	<div class="col-md-12">
		<label for="address_line2" class="v2-label">@lang('Address Line 2')</label>
		<input type="text" class="v2-input" id="address_line2" name="address_line2" value="{{ old('address_line2', $cardholder->address_line2 ?? '') }}" placeholder="@lang('Apartment, suite, unit, building, floor, etc.')">
	</div>
	<div class="col-md-4">
		<label for="city" class="v2-label">@lang('City')</label>
		<input type="text" class="v2-input" id="city" name="city" value="{{ old('city', $cardholder->city ?? '') }}" placeholder="@lang('Enter city')">
	</div>
	<div class="col-md-4">
		<label for="state" class="v2-label">@lang('State')</label>
		<input type="text" class="v2-input" id="state" name="state" value="{{ old('state', $cardholder->state ?? '') }}" placeholder="@lang('Enter state')">
	</div>
	<div class="col-md-4">
		<label for="postal_code" class="v2-label">@lang('Postal Code')</label>
		<input type="text" class="v2-input" id="postal_code" name="postal_code" value="{{ old('postal_code', $cardholder->postal_code ?? '') }}" placeholder="@lang('Enter postal/ZIP code')">
	</div>
	<div class="col-md-4">
		<label for="country" class="v2-label">@lang('Country')</label>
		<select class="v2-input" id="country" name="country">
			<option value="">@lang('Select Country')</option>
			@foreach($allCountries as $country)
				<option value="{{ $country['code'] }}" {{ old('country', $cardholder->country ?? '') == $country['code'] ? 'selected' : '' }}>
					{{ title($country['name']) }}
				</option>
			@endforeach
		</select>
	</div>
</div>
