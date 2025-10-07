# Audio Duration Extraction Feature

## 🎯 **Feature Overview**

Added functionality to extract the actual duration from audio files during the Vapi sync process and update the database with the accurate duration.

## 🔧 **Problem Solved**

### **Issue**
- Vapi API call data might not always include accurate duration information
- Duration calculated from timestamps might be inaccurate due to network delays or processing time
- Need accurate duration for analytics and reporting

### **Solution**
- Extract actual duration from the downloaded audio file
- Use multiple methods for maximum compatibility
- Update database with accurate duration from audio file

## 🚀 **Implementation**

### **1. Multiple Extraction Methods**

#### **Method 1: getID3 Library (Primary)**
```php
// Uses the getID3 library (already installed)
$getID3 = new getID3();
$fileInfo = $getID3->analyze($fullPath);

if (isset($fileInfo['playtime_seconds'])) {
    return (int) $fileInfo['playtime_seconds'];
}
```

#### **Method 2: FFmpeg (Fallback)**
```php
// Uses FFmpeg if available
$command = "ffmpeg -i " . escapeshellarg($fullPath) . " 2>&1";
$output = shell_exec($command);

// Parse duration from FFmpeg output
if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $output, $matches)) {
    $hours = (int) $matches[1];
    $minutes = (int) $matches[2];
    $seconds = (int) $matches[3];
    $centiseconds = (int) $matches[4];
    
    $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds + ($centiseconds / 100);
    return (int) $totalSeconds;
}
```

#### **Method 3: soxi (Alternative)**
```php
// Uses soxi (Sound eXchange Info) if available
$command = "soxi -D " . escapeshellarg($fullPath) . " 2>/dev/null";
$output = shell_exec($command);

if (is_numeric($output)) {
    return (int) $output;
}
```

### **2. Integration with Sync Process**

```php
// During audio download in sync process
$duration = $this->extractAudioDuration($filePath);
if ($duration !== null) {
    $callLog->duration = $duration;
    $this->line("Extracted duration from audio: {$duration} seconds");
}
```

## 📊 **Usage Examples**

### **1. Test Duration Extraction**
```bash
# Test with existing audio file
php artisan test:audio-duration 0U5asv.wav

# Expected output:
# Testing audio duration extraction for: 0U5asv.wav
# File path: recordings/0U5asv.wav
# File exists, extracting duration...
# ✅ Duration extracted successfully: 125 seconds
# Formatted duration: 02:05
```

### **2. Sync with Duration Extraction**
```bash
# Run sync with duration extraction
php artisan vapi:sync-calls --limit=5

# Expected output:
# Downloaded recording for call: call_123 -> 0U5asv.wav
# Extracted duration from audio: 125 seconds
```

### **3. Verify Database Updates**
```bash
# Check call logs with extracted duration
php artisan tinker
>>> App\Models\CallLog::whereNotNull('call_record_file_name')->get(['call_id', 'duration', 'call_record_file_name'])
```

## 🔍 **Testing Commands**

### **1. Test Audio Duration Extraction**
```bash
# Test with specific file
php artisan test:audio-duration 0U5asv.wav

# Test with different file formats
php artisan test:audio-duration Kj9mNp.wav
php artisan test:audio-duration Xy2zQr.wav
```

### **2. Check Available Tools**
```bash
# Check if getID3 is available
composer show | grep getid3

# Check if FFmpeg is available
which ffmpeg

# Check if soxi is available
which soxi
```

### **3. Test Sync Process**
```bash
# Run sync and check duration extraction
php artisan vapi:sync-calls --limit=1

# Check logs for duration extraction
tail -f storage/logs/laravel.log | grep "duration"
```

## 📋 **File Structure**

### **Audio Files**
```
storage/app/public/recordings/
├── 0U5asv.wav (125 seconds)
├── Kj9mNp.wav (89 seconds)
├── Xy2zQr.wav (203 seconds)
└── ...
```

### **Database Updates**
```sql
-- CallLog table with extracted duration
call_id: "call_123"
call_record_file_name: "0U5asv.wav"
duration: 125 (extracted from audio file)
public_audio_url: "http://domain.com/p/0U5asv"
```

## 🎯 **Expected Results**

### **Before Implementation**
- ❌ Duration calculated from timestamps only
- ❌ Potentially inaccurate duration due to network delays
- ❌ Missing duration for calls without timestamps
- ❌ Inconsistent duration data

### **After Implementation**
- ✅ **Accurate Duration**: Extracted from actual audio file
- ✅ **Multiple Methods**: Fallback options for different environments
- ✅ **Consistent Data**: Same extraction method for all recordings
- ✅ **Better Analytics**: Accurate duration for reporting

## 🔧 **Technical Details**

### **Supported Audio Formats**
- **WAV**: Primary format from Vapi recordings
- **MP3**: Supported by getID3 library
- **Other formats**: Supported by FFmpeg and soxi

### **Duration Precision**
- **getID3**: Accurate to seconds
- **FFmpeg**: Accurate to centiseconds
- **soxi**: Accurate to seconds

### **Error Handling**
- **File not found**: Graceful handling with warnings
- **Extraction failure**: Falls back to timestamp calculation
- **Multiple methods**: Tries all available methods before giving up

## 🚨 **Requirements**

### **1. PHP Libraries**
- **getID3**: Already installed (`james-heinrich/getid3`)
- **FFmpeg**: Optional, for enhanced compatibility
- **soxi**: Optional, for alternative extraction

### **2. System Requirements**
- **Storage**: Sufficient space for audio files
- **Permissions**: Read access to audio files
- **Memory**: Adequate memory for audio processing

### **3. Performance Considerations**
- **Processing Time**: Audio analysis adds time to sync process
- **Memory Usage**: Large audio files may increase memory usage
- **Storage**: Audio files require significant storage space

## 🔮 **Future Enhancements**

### **1. Performance Optimization**
- **Background Processing**: Move duration extraction to background jobs
- **Caching**: Cache duration results to avoid re-processing
- **Batch Processing**: Process multiple files simultaneously

### **2. Additional Features**
- **Audio Quality Analysis**: Extract bitrate, sample rate, etc.
- **Waveform Generation**: Create waveform images for visualization
- **Transcription Integration**: Link duration with transcript timing

### **3. Monitoring**
- **Extraction Success Rate**: Track successful vs failed extractions
- **Performance Metrics**: Monitor extraction time and resource usage
- **Error Reporting**: Detailed error reporting for troubleshooting

## 📊 **Benefits**

### **1. Data Accuracy**
- **Precise Duration**: Actual audio length, not estimated
- **Consistent Results**: Same extraction method for all files
- **Reliable Analytics**: Accurate duration for reporting

### **2. Better User Experience**
- **Accurate Display**: Correct duration shown in UI
- **Better Analytics**: Precise call duration statistics
- **Improved Reporting**: Accurate duration-based reports

### **3. System Reliability**
- **Multiple Fallbacks**: Multiple extraction methods
- **Error Resilience**: Graceful handling of extraction failures
- **Compatibility**: Works with different audio formats

The audio duration extraction feature provides **accurate, reliable duration data** for all call recordings! 🎉

**Key Benefits:**
- ✅ **Accurate Duration**: Extracted from actual audio files
- ✅ **Multiple Methods**: Robust extraction with fallbacks
- ✅ **Better Analytics**: Precise duration for reporting
- ✅ **Consistent Data**: Same extraction method for all recordings 