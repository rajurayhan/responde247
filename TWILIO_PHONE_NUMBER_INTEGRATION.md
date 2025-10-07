# Twilio Phone Number Integration

This document describes the integration of Twilio phone number management with the assistant creation process.

## Overview

The system allows users to search for available Twilio phone numbers by area code, select a number, and purchase it automatically when creating an assistant. This integration includes:

1. **Search by Area Code**: Search available phone numbers by specific area code
2. **Number Selection**: Select a phone number without immediate purchase
3. **Delayed Purchase**: Purchase selected number when creating assistant
4. **Vapi Assignment**: Connect purchased numbers to Vapi assistants

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
TWILIO_ACCOUNT_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
```

### Services Configuration

The Twilio configuration is defined in `config/services.php`:

```php
'twilio' => [
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
],
```

## API Endpoints

### Get Available Phone Numbers

```http
GET /api/twilio/available-numbers
```

**Query Parameters:**
- `country_code` (optional): Country code (default: US)
- `limit` (optional): Number of results (default: 10, max: 50)
- `area_code` (optional): Area code to search for (e.g., 212)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "phone_number": "+1212567890",
      "friendly_name": "+1 (212) 567-890",
      "locality": "New York",
      "region": "NY",
      "country": "US"
    }
  ]
}
```

### Purchase Phone Number

```http
POST /api/twilio/purchase-number
```

**Request Body:**
```json
{
  "phone_number": "+1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "sid": "PN1234567890",
    "phone_number": "+1234567890",
    "friendly_name": "+1 (234) 567-890",
    "status": "active"
  },
  "message": "Phone number purchased successfully"
}
```

### Get Purchased Phone Numbers

```http
GET /api/twilio/purchased-numbers
```

**Query Parameters:**
- `limit` (optional): Number of results (default: 50, max: 100)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "sid": "PN1234567890",
      "phone_number": "+1234567890",
      "friendly_name": "+1 (234) 567-890",
      "status": "active"
    }
  ]
}
```

### Release Phone Number

```http
DELETE /api/twilio/release-number
```

**Request Body:**
```json
{
  "phone_number_sid": "PN1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Phone number released successfully"
}
```

## Frontend Integration

### Assistant Form

The assistant creation form includes a phone number search and selection section:

```vue
<!-- Phone Number Purchase Section -->
<div v-if="isCreating" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
  <div class="flex items-center justify-between mb-3">
    <h4 class="text-sm font-medium text-blue-900">Purchase Twilio Phone Number</h4>
    <div class="flex items-center space-x-2">
      <input
        v-model="areaCode"
        type="text"
        placeholder="Area Code (e.g., 212)"
        maxlength="3"
        class="text-xs px-2 py-1 border border-gray-300 rounded"
      />
      <button @click="loadAvailableNumbers">Get Available Numbers</button>
    </div>
  </div>
  
  <!-- Available Numbers List -->
  <div v-if="availableNumbers.length > 0" class="space-y-2">
    <p class="text-xs text-blue-700 mb-2">Select a phone number to purchase when creating assistant:</p>
    <div v-for="number in availableNumbers" :key="number.phone_number">
      <div class="flex items-center space-x-2">
        <input
          type="radio"
          :id="number.phone_number"
          :value="number.phone_number"
          v-model="selectedPhoneNumber"
          name="phoneNumber"
        />
        <label :for="number.phone_number">
          {{ number.phone_number }}
          <span v-if="number.locality">
            ({{ number.locality }}, {{ number.region }})
          </span>
        </label>
      </div>
    </div>
  </div>
  
  <!-- Manual Input -->
  <input v-model="form.metadata.assistant_phone_number" type="tel" />
</div>
```

### JavaScript Functions

```javascript
// Load available phone numbers with area code
const loadAvailableNumbers = async () => {
  const params = {}
  if (areaCode.value.trim()) {
    params.area_code = areaCode.value.trim()
  }
  
  const response = await axios.get('/api/twilio/available-numbers', { params })
  availableNumbers.value = response.data.data
  selectedPhoneNumber.value = '' // Reset selection
}

// Selected phone number is automatically included when creating assistant
const assistantData = {
  // ... other data
  selected_phone_number: selectedPhoneNumber.value
}
```

## Backend Implementation

### TwilioService

The `TwilioService` handles all Twilio API interactions with area code support:

```php
class TwilioService
{
    public function getAvailableNumbers($countryCode = 'US', $limit = 10, $areaCode = null)
    {
        $params = [
            'Limit' => $limit,
            'VoiceEnabled' => true,
            'SmsEnabled' => true
        ];
        
        // Add area code filter if provided
        if ($areaCode) {
            $params['AreaCode'] = $areaCode;
        }
        
        // Make API call with parameters
    }
    
    public function purchaseNumber($phoneNumber)
    public function getPurchasedNumbers($limit = 50)
    public function releaseNumber($phoneNumberSid)
}
```

### AssistantController Integration

Phone number purchase is integrated into the assistant creation process:

```php
// Handle phone number purchase and assignment
$phoneNumber = null;
if ($request->has('selected_phone_number') && $request->selected_phone_number) {
    $twilioService = app(\App\Services\TwilioService::class);
    
    // Purchase the selected phone number
    $purchaseResult = $twilioService->purchaseNumber($request->selected_phone_number);
    
    if ($purchaseResult['success']) {
        $phoneNumber = $request->selected_phone_number;
        $data['metadata']['assistant_phone_number'] = $phoneNumber;
    } else {
        return response()->json([
            'success' => false,
            'message' => 'Failed to purchase phone number: ' . ($purchaseResult['message'] ?? 'Unknown error')
        ], 500);
    }
}

// Create assistant in Vapi
$vapiAssistant = $this->vapiService->createAssistant($data);

// Assign phone number to Vapi if purchased
if ($phoneNumber) {
    $this->vapiService->assignPhoneNumber($vapiAssistant['id'], $phoneNumber);
}
```

### VapiService Integration

The `VapiService` includes phone number assignment:

```php
public function assignPhoneNumber($assistantId, $phoneNumber)
{
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey,
        'Content-Type' => 'application/json',
    ])->post($this->baseUrl . '/phone-number', [
        'assistantId' => $assistantId,
        'phoneNumber' => $phoneNumber
    ]);
    
    return $response->successful() ? $response->json() : null;
}
```

## User Experience

### Creating an Assistant with Phone Number

1. **Navigate to Assistant Form**: Go to create assistant page
2. **Phone Number Section**: Find the "Purchase Twilio Phone Number" section
3. **Enter Area Code**: Type desired area code (e.g., 212 for New York)
4. **Get Available Numbers**: Click "Get Available Numbers" button
5. **Select Number**: Choose from the list of available numbers using radio buttons
6. **Complete Form**: Fill in other assistant details
7. **Create Assistant**: Submit the form - phone number will be purchased automatically

### Phone Number Assignment

- **Delayed Purchase**: Numbers are purchased only when assistant is created
- **Automatic Assignment**: Purchased numbers are automatically assigned to Vapi
- **Database Storage**: Phone numbers are stored in the `assistants` table
- **Vapi Integration**: Numbers are connected to Vapi assistants via API

## Error Handling

### Common Errors

1. **Twilio API Errors**:
   - Invalid credentials
   - Insufficient funds
   - Number already taken
   - Invalid area code

2. **Vapi Assignment Errors**:
   - Assistant not found
   - Invalid phone number format
   - Assignment failed

### Error Responses

```json
{
  "success": false,
  "message": "Failed to purchase phone number: Insufficient funds"
}
```

## Testing

### Running Tests

```bash
php artisan test tests/Feature/TwilioPhoneNumberTest.php
php artisan test tests/Feature/AssistantPhoneNumberTest.php
```

### Test Coverage

- ✅ Get available phone numbers
- ✅ Search by area code
- ✅ Purchase phone numbers
- ✅ Get purchased phone numbers
- ✅ Release phone numbers
- ✅ Validation errors
- ✅ Error handling
- ✅ Assistant creation with selected phone number
- ✅ Assistant creation without phone number
- ✅ Purchase failure handling

## Security Considerations

1. **Authentication**: All endpoints require authentication
2. **Authorization**: Admin users have full access
3. **Rate Limiting**: Consider implementing rate limits
4. **Error Logging**: All errors are logged for debugging

## Future Enhancements

### Potential Improvements

1. **Number Pooling**: Shared phone numbers across assistants
2. **Geographic Selection**: Choose numbers by location
3. **Number Porting**: Port existing numbers to Twilio
4. **Usage Analytics**: Track phone number usage
5. **Cost Management**: Monitor and optimize costs

### Advanced Features

1. **Webhook Integration**: Handle incoming calls/SMS
2. **Call Routing**: Route calls to specific assistants
3. **SMS Integration**: Handle text messages
4. **Call Recording**: Record and store calls
5. **Analytics Dashboard**: Phone number performance metrics

## Troubleshooting

### Common Issues

1. **No Available Numbers**:
   - Check Twilio account balance
   - Verify area code format
   - Contact Twilio support

2. **Purchase Fails**:
   - Verify account credentials
   - Check account status
   - Ensure sufficient funds

3. **Vapi Assignment Fails**:
   - Verify assistant exists
   - Check phone number format
   - Review Vapi API logs

### Debug Steps

1. **Check Twilio Account**:
   ```bash
   curl -u $TWILIO_ACCOUNT_SID:$TWILIO_AUTH_TOKEN \
     https://api.twilio.com/2010-04-01/Accounts/$TWILIO_ACCOUNT_SID.json
   ```

2. **Test Area Code Search**:
   ```bash
   curl -X GET "http://localhost/api/twilio/available-numbers?area_code=212" \
     -H "Authorization: Bearer $TOKEN"
   ```

3. **Test Vapi Assignment**:
   ```bash
   curl -X POST https://api.vapi.ai/phone-number \
     -H "Authorization: Bearer $VAPI_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{"assistantId": "assistant_id", "phoneNumber": "+1234567890"}'
   ```

4. **Check Logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i twilio
   ``` 