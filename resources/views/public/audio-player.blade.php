<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Recording - {{ $callInfo['call_id'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3"> 
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ $callInfo['caller_number'] ? $callInfo['caller_number'] : 'Unknown Caller' }}</h1>
                        <p class="text-sm text-gray-500">Duration: {{ $callInfo['formatted_duration'] }}</p>
                        <p class="text-sm text-gray-500">Assistant: {{ $callInfo['assistant']['name'] }}</p>
                        <p class="text-sm text-gray-500">Call ID: {{ $callInfo['call_id'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audio Player -->
        @if($callInfo['has_recording'])
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div id="audio-player-container">
                <!-- Audio Player will be rendered here -->
            </div>
        </div>
        @endif

        <!-- Call Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <button 
                onclick="toggleSection('call-details')" 
                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition-colors"
            >
                <h2 class="text-lg font-medium text-gray-900">Call Details</h2>
                <svg id="call-details-icon" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="call-details-content" class="px-6 pb-6 hidden">
                <!-- Assistant Information -->
                @if($callInfo['assistant'])
                <div class="mb-6">
                    <h3 class="text-md font-medium text-gray-900 mb-3">Assistant Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Assistant Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $callInfo['assistant']['name'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($callInfo['assistant']['type'] === 'production') bg-blue-100 text-blue-800
                                    @elseif($callInfo['assistant']['type'] === 'demo') bg-purple-100 text-purple-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($callInfo['assistant']['type']) }}
                                </span>
                            </dd>
                        </div>
                        @if($callInfo['assistant']['phone_number'])
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Assistant Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $callInfo['assistant']['phone_number'] }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $callInfo['assistant']['created_at'] }}</dd>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Call Information -->
                <div>
                    <h3 class="text-md font-medium text-gray-900 mb-3">Call Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($callInfo['status'] === 'completed') bg-green-100 text-green-800
                                    @elseif($callInfo['status'] === 'failed') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($callInfo['status']) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Direction</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($callInfo['direction']) }}</dd>
                        </div>
                        @if($callInfo['phone_number'])
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Assistant Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $callInfo['phone_number'] }}</dd>
                        </div>
                        @endif
                        @if($callInfo['caller_number'])
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Caller Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $callInfo['caller_number'] }}</dd>
                        </div>
                        @endif
                        @if($callInfo['start_time'])
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Start Time</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $callInfo['start_time'] }}</dd>
                        </div>
                        @endif
                        @if($callInfo['end_time'])
                        <div>
                            <dt class="text-sm font-medium text-gray-500">End Time</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $callInfo['end_time'] }}</dd>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        @if($callInfo['summary'])
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <button 
                onclick="toggleSection('summary')" 
                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition-colors"
            >
                <h2 class="text-lg font-medium text-gray-900">Call Summary</h2>
                <svg id="summary-icon" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="summary-content" class="px-6 pb-6 hidden">
                <div class="bg-gray-50 p-4 rounded-md">
                    <p class="text-sm text-gray-900">{{ $callInfo['summary'] }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Transcript -->
        @if($callInfo['transcript'])
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <button 
                onclick="toggleSection('transcript')" 
                class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 focus:outline-none focus:bg-gray-50 transition-colors"
            >
                <h2 class="text-lg font-medium text-gray-900">Call Transcript</h2>
                <svg id="transcript-icon" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="transcript-content" class="px-6 pb-6 hidden">
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @php
                        // Parse transcript into chat bubbles
                        $transcript = $callInfo['transcript'] ?? '';
                        $lines = explode("\n", $transcript);
                        $messages = [];
                        $currentSpeaker = null;
                        $currentMessage = '';
                        
                        // Get assistant name for display
                        $assistantName = $callInfo['assistant']['name'] ?? 'Assistant';
                        
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (empty($line)) continue;
                            
                            // Check if line starts with speaker name (common patterns)
                            if (preg_match('/^(Assistant|AI|Bot|Caller|Customer|User|Human):\s*(.*)$/i', $line, $matches)) {
                                // Save previous message if exists
                                if ($currentSpeaker && $currentMessage) {
                                    $messages[] = [
                                        'speaker' => $currentSpeaker,
                                        'message' => trim($currentMessage)
                                    ];
                                }
                                
                                // Normalize speaker names
                                $speaker = $matches[1];
                                $speakerLower = strtolower($speaker);
                                
                                // Map speaker names to display names
                                if (in_array($speakerLower, ['assistant', 'ai', 'bot'])) {
                                    $currentSpeaker = $assistantName;
                                } elseif (in_array($speakerLower, ['user', 'customer', 'human'])) {
                                    $currentSpeaker = 'Caller';
                                } else {
                                    $currentSpeaker = $speaker;
                                }
                                
                                $currentMessage = $matches[2];
                            } else {
                                // Continue current message
                                $currentMessage .= ($currentMessage ? "\n" : '') . $line;
                            }
                        }
                        
                        // Add last message
                        if ($currentSpeaker && $currentMessage) {
                            $messages[] = [
                                'speaker' => $currentSpeaker,
                                'message' => trim($currentMessage)
                            ];
                        }
                        
                        // If no structured messages found, treat as single message
                        if (empty($messages) && !empty($transcript)) {
                            $messages[] = [
                                'speaker' => $assistantName,
                                'message' => $transcript
                            ];
                        }
                    @endphp
                    
                    @if(!empty($messages))
                        @foreach($messages as $message)
                            @php
                                // Check if speaker is the assistant (by comparing with assistant name)
                                $isAssistant = ($message['speaker'] === $assistantName);
                                $isCaller = ($message['speaker'] === 'Caller');
                            @endphp
                            
                            <div class="flex {{ $isAssistant ? 'justify-start' : 'justify-end' }}">
                                <div class="max-w-xs lg:max-w-md">
                                    <!-- Speaker Label -->
                                    <div class="flex items-center {{ $isAssistant ? 'justify-start' : 'justify-end' }} mb-1">
                                        <span class="text-xs font-medium {{ $isAssistant ? 'text-blue-600' : 'text-green-600' }}">
                                            {{ $message['speaker'] }}
                                        </span>
                                    </div>
                                    
                                    <!-- Message Bubble -->
                                    <div class="relative">
                                        <div class="px-4 py-2 rounded-lg {{ $isAssistant ? 'bg-blue-100 text-blue-900' : 'bg-green-100 text-green-900' }}">
                                            <p class="text-sm whitespace-pre-wrap">{{ $message['message'] }}</p>
                                        </div>
                                        
                                        <!-- Tail -->
                                        <div class="absolute {{ $isAssistant ? 'left-0 -ml-1' : 'right-0 -mr-1' }} top-2">
                                            <div class="w-2 h-2 {{ $isAssistant ? 'bg-blue-100' : 'bg-green-100' }} transform rotate-45"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Fallback for unstructured transcript -->
                        <div class="bg-gray-50 p-4 rounded-md">
                            <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $callInfo['transcript'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Audio Player Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const callInfo = @json($callInfo);
            
            if (callInfo.has_recording && callInfo.public_audio_url) {
                createAudioPlayer(callInfo.public_audio_url);
            }
        });

        // Toggle collapsible sections
        function toggleSection(sectionId) {
            const content = document.getElementById(sectionId + '-content');
            const icon = document.getElementById(sectionId + '-icon');
            
            if (content.classList.contains('hidden')) {
                // Expand
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                // Collapse
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        function createAudioPlayer(audioUrl) {
            const container = document.getElementById('audio-player-container');
            
            container.innerHTML = `
                <div class="audio-player bg-gray-50 rounded-lg p-4">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500" id="time-display">0:00 / 0:00</p>
                            </div>
                        </div>
                        
                        <!-- Download Button -->
                        <a 
                            href="${audioUrl}" 
                            download 
                            class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download
                        </a>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative mb-4">
                        <div class="bg-gray-200 rounded-full h-2">
                            <div 
                                id="progress-bar"
                                class="bg-green-500 h-2 rounded-full transition-all duration-100"
                                style="width: 0%"
                            ></div>
                        </div>
                        <input
                            id="progress-slider"
                            type="range"
                            min="0"
                            max="100"
                            value="0"
                            class="absolute inset-0 w-full h-2 opacity-0 cursor-pointer"
                        />
                    </div>

                    <!-- Control Buttons -->
                    <div class="flex items-center justify-center space-x-4 mb-4">
                        <!-- Rewind 10s -->
                        <button
                            id="rewind-btn"
                            class="p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 rounded-full"
                            disabled
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                            </svg>
                        </button>

                        <!-- Play/Pause -->
                        <button
                            id="play-pause-btn"
                            class="p-3 bg-green-500 text-white rounded-full hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled
                        >
                            <svg id="play-icon" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <svg id="pause-icon" class="w-6 h-6 hidden" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                            </svg>
                        </button>

                        <!-- Forward 10s -->
                        <button
                            id="forward-btn"
                            class="p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 rounded-full"
                            disabled
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.934 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.334-4z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.934 12.8a1 1 0 000-1.6L14.6 7.2A1 1 0 0013 8v8a1 1 0 001.6.8l5.334-4z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Volume Control -->
                    <div class="flex items-center space-x-3">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                        </svg>
                        <input
                            id="volume-slider"
                            type="range"
                            min="0"
                            max="1"
                            step="0.1"
                            value="0.7"
                            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                        />
                        <span id="volume-display" class="text-xs text-gray-500 w-8 text-right">70%</span>
                    </div>
                </div>

                <!-- Hidden Audio Element -->
                <audio
                    id="audio-element"
                    src="${audioUrl}"
                    preload="metadata"
                    crossorigin="anonymous"
                ></audio>
            `;

            // Initialize audio player functionality
            initializeAudioPlayer();
        }

        function initializeAudioPlayer() {
            const audio = document.getElementById('audio-element');
            const playPauseBtn = document.getElementById('play-pause-btn');
            const rewindBtn = document.getElementById('rewind-btn');
            const forwardBtn = document.getElementById('forward-btn');
            const progressSlider = document.getElementById('progress-slider');
            const progressBar = document.getElementById('progress-bar');
            const volumeSlider = document.getElementById('volume-slider');
            const volumeDisplay = document.getElementById('volume-display');
            const timeDisplay = document.getElementById('time-display');
            const playIcon = document.getElementById('play-icon');
            const pauseIcon = document.getElementById('pause-icon');

            let isPlaying = false;
            let isLoaded = false;

            // Set initial volume
            audio.volume = 0.7;

            // Event listeners
            audio.addEventListener('loadedmetadata', () => {
                isLoaded = true;
                progressSlider.max = audio.duration;
                updateTimeDisplay();
                enableControls();
            });

            audio.addEventListener('timeupdate', () => {
                if (isLoaded) {
                    progressSlider.value = audio.currentTime;
                    progressBar.style.width = (audio.currentTime / audio.duration) * 100 + '%';
                    updateTimeDisplay();
                }
            });

            audio.addEventListener('play', () => {
                isPlaying = true;
                playIcon.classList.add('hidden');
                pauseIcon.classList.remove('hidden');
            });

            audio.addEventListener('pause', () => {
                isPlaying = false;
                playIcon.classList.remove('hidden');
                pauseIcon.classList.add('hidden');
            });

            audio.addEventListener('ended', () => {
                isPlaying = false;
                playIcon.classList.remove('hidden');
                pauseIcon.classList.add('hidden');
            });

            playPauseBtn.addEventListener('click', () => {
                if (!isLoaded) return;
                
                if (isPlaying) {
                    audio.pause();
                } else {
                    audio.play().catch(error => {
                        console.error('Error playing audio:', error);
                    });
                }
            });

            rewindBtn.addEventListener('click', () => {
                if (!isLoaded) return;
                audio.currentTime = Math.max(0, audio.currentTime - 10);
            });

            forwardBtn.addEventListener('click', () => {
                if (!isLoaded) return;
                audio.currentTime = Math.min(audio.duration, audio.currentTime + 10);
            });

            progressSlider.addEventListener('input', () => {
                if (!isLoaded) return;
                audio.currentTime = parseFloat(progressSlider.value);
            });

            volumeSlider.addEventListener('input', () => {
                const volume = parseFloat(volumeSlider.value);
                audio.volume = volume;
                volumeDisplay.textContent = Math.round(volume * 100) + '%';
            });

            function updateTimeDisplay() {
                const current = formatTime(audio.currentTime);
                const duration = formatTime(audio.duration);
                timeDisplay.textContent = `${current} / ${duration}`;
            }

            function formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return '0:00';
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.floor(seconds % 60);
                return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            }

            function enableControls() {
                playPauseBtn.disabled = false;
                rewindBtn.disabled = false;
                forwardBtn.disabled = false;
                progressSlider.disabled = false;
            }
        }
    </script>
</body>
</html>
