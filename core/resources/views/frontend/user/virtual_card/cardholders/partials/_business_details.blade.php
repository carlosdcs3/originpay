<div class="row g-3 mt-1 business-fields">
	<div class="col-md-6">
		<label for="business_name" class="v2-label">@lang('Business Name')</label>
		<input type="text" class="v2-input" id="business_name" name="business_name" value="{{ old('business_name', $business->business_name ?? '') }}" placeholder="@lang('Enter business name')">
	</div>
	<div class="col-md-6">
		<label for="registration_number" class="v2-label">@lang('Registration Number')</label>
		<input type="text" class="v2-input" id="registration_number" name="registration_number" value="{{ old('registration_number', $business->registration_number ?? '') }}" placeholder="@lang('Enter registration number')">
	</div>
	<div class="col-md-6">
		<label for="tin" class="v2-label">@lang('TIN')</label>
		<input type="text" class="v2-input" id="tin" name="tin" value="{{ old('tin', $business->tin ?? '') }}" placeholder="@lang('Enter TIN')">
	</div>
	<div class="col-md-6">
		<label for="business_type" class="v2-label">@lang('Business Type')</label>
		<input type="text" class="v2-input" id="business_type" name="business_type" value="{{ old('business_type', $business->business_type ?? '') }}" placeholder="@lang('Enter business type')">
	</div>
	<div class="col-md-6">
		<label for="contact_email" class="v2-label">@lang('Contact Email')</label>
		<input type="email" class="v2-input" id="contact_email" name="contact_email" value="{{ old('contact_email', $business->contact_email ?? '') }}" placeholder="@lang('Enter contact email')">
	</div>
	<div class="col-md-6">
		<label for="contact_phone" class="v2-label">@lang('Contact Phone')</label>
		<input type="text" class="v2-input" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $business->contact_phone ?? '') }}" placeholder="@lang('Enter contact phone')">
	</div>
	<div class="col-md-12">
		<label for="address_line1_b" class="v2-label">@lang('Business Address Line 1')</label>
		<input type="text" class="v2-input" id="address_line1_b" name="address_line1_b" value="{{ old('address_line1_b', $business->address_line1 ?? '') }}" placeholder="@lang('Business street address')">
	</div>
	<div class="col-md-12">
		<label for="address_line2_b" class="v2-label">@lang('Business Address Line 2')</label>
		<input type="text" class="v2-input" id="address_line2_b" name="address_line2_b" value="{{ old('address_line2_b', $business->address_line2 ?? '') }}" placeholder="@lang('Business address line 2')">
	</div>
	<div class="col-md-4">
		<label for="city_b" class="v2-label">@lang('City')</label>
		<input type="text" class="v2-input" id="city_b" name="city_b" value="{{ old('city_b', $business->city ?? '') }}" placeholder="@lang('Enter city')">
	</div>
	<div class="col-md-4">
		<label for="state_b" class="v2-label">@lang('State')</label>
		<input type="text" class="v2-input" id="state_b" name="state_b" value="{{ old('state_b', $business->state ?? '') }}" placeholder="@lang('Enter state')">
	</div>
	<div class="col-md-4">
		<label for="postal_code_b" class="v2-label">@lang('Postal Code')</label>
		<input type="text" class="v2-input" id="postal_code_b" name="postal_code_b" value="{{ old('postal_code_b', $business->postal_code ?? '') }}" placeholder="@lang('Enter postal/ZIP code')">
	</div>
	<div class="col-md-4">
		<label for="country_b" class="v2-label">@lang('Country')</label>
		<select class="v2-input" id="country_b" name="country_b">
			<option value="">@lang('Select Country')</option>
			@foreach($allCountries as $country)
				<option value="{{ $country['code']}}" {{ old('country_b', $business->country ?? '') == $country['code'] ? 'selected' : '' }}>
					{{ title($country['name']) }}
				</option>
			@endforeach
		</select>
	</div>
</div>
