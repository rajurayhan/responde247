<template>
  <div class="space-y-6">
    <!-- Voice Provider Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Voice Provider *</label>
      <select
        v-model="voiceConfig.provider"
        @change="onProviderChange"
        data-field="voice_provider"
        :class="[
          'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
          fieldErrors.provider 
            ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
            : 'border-gray-300 focus:border-green-500 bg-white'
        ]"
      >
        <option value="vapi">Sulus (Built-in)</option>
        <option value="11labs">ElevenLabs</option>
        <option value="azure" disabled>Azure Speech (Upcoming)</option>
        <option value="google" disabled>Google Cloud Speech (Upcoming)</option>
        <option value="aws" disabled>AWS Polly (Upcoming)</option>
        <option value="openai" disabled>OpenAI TTS (Upcoming)</option>
        <option value="playht" disabled>PlayHT (Upcoming)</option>
        <option value="deepgram" disabled>Deepgram (Upcoming)</option>
        <option value="rime" disabled>Rime (Upcoming)</option>
        <option value="custom" disabled>Custom (Upcoming)</option>
      </select>
      <p v-if="fieldErrors.provider" class="text-xs text-red-600 mt-1">{{ fieldErrors.provider }}</p>
      <p v-else class="text-xs text-gray-500 mt-1">Select the voice provider for your assistant</p>
    </div>

    <!-- Provider-Specific Configuration -->
    <div v-if="voiceConfig.provider" class="space-y-4">
      <!-- Voice Selection (Common for all providers) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Voice *</label>
        <div class="relative">
          <input
            v-model="voiceSearchQuery"
            type="text"
            placeholder="Search voices..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 mb-2"
          />
          <div class="absolute right-2 top-2 text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </div>
        </div>
        <select
          v-model="voiceConfig.voiceId"
          data-field="voice_voiceId"
          :class="[
            'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 max-h-60 overflow-y-auto',
            fieldErrors.voiceId 
              ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
              : 'border-gray-300 focus:border-green-500 bg-white'
          ]"
        >
          <option v-for="voice in getFilteredVoices()" :key="voice.value" :value="voice.value">
            {{ voice.label }}
          </option>
        </select>
        <p v-if="fieldErrors.voiceId" class="text-xs text-red-600 mt-1">{{ fieldErrors.voiceId }}</p>
      </div>

      <!-- Model Selection (for providers that support it) -->
      <div v-if="getAvailableModels().length > 0">
        <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
        <select
          v-model="voiceConfig.model"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
        >
          <option v-for="model in getAvailableModels()" :key="model.value" :value="model.value">
            {{ model.label }}
          </option>
        </select>
      </div>

      <!-- Speed Control (for providers that support it) -->
      <div v-if="supportsSpeed()">
        <label class="block text-sm font-medium text-gray-700 mb-2">Speed</label>
        <input
          v-model.number="voiceConfig.speed"
          type="range"
          min="0.25"
          max="4"
          step="0.1"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
        />
        <div class="flex justify-between text-xs text-gray-500 mt-1">
          <span>0.25x (Slow)</span>
          <span>{{ voiceConfig.speed }}x</span>
          <span>4x (Fast)</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue'

export default {
  name: 'VoiceConfiguration',
  props: {
    voice: {
      type: Object,
      default: () => ({})
    },
    fieldErrors: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['update:voice'],
  setup(props, { emit }) {
    const isUpdatingFromProps = ref(false)
    const voiceSearchQuery = ref('')
    const voiceConfig = ref({
      provider: 'vapi',
      voiceId: 'spencer',
      model: '',
      speed: 1
    })

    // Voice options for each provider
    const voiceOptions = {
      vapi: [
        { value: 'Cole', label: 'Cole (Male, Professional)' },
        { value: 'Harry', label: 'Harry (Male, Professional)' },
        { value: 'Spencer', label: 'Spencer (Female, Energetic)' },
        { value: 'Neha', label: 'Neha (Female, Professional)' },
        { value: 'Kylie', label: 'Kylie (Female, Professional)' },
        { value: 'Savannah', label: 'Savannah (Female, Professional)' },
        { value: 'Paige', label: 'Paige (Female, Professional)' },
        { value: 'Rohan', label: 'Rohan (Male, Professional)' },
        { value: 'Hana', label: 'Hana (Female, Professional)' },
        { value: 'Elliot', label: 'Elliot (Male, Professional)' },
      ],
      '11labs': [
        { value: 'pNInz6obpgDQGcFmaJgB', label: 'Adam (Male)' },
        { value: 'EXAVITQu4vr4xnSDxMaL', label: 'Bella (Female)' },
        { value: 'VR6AewLTigWG4xSOukaG', label: 'Josh (Male)' },
        { value: 'AZnzlk1XvdvUeBnXmlld', label: 'Domi (Female)' },
        { value: 'MF3mGyEYCl7XYWbV9V6O', label: 'Elli (Female)' },
        { value: 'TxGEqnHWrfWFTfGW9XjX', label: 'Rachel (Female)' },
        { value: 'VR6AewLTigWG4xSOukaG', label: 'Drew (Male)' },
        { value: 'pMsXgVXv3BLzUgSXRplE', label: 'Clyde (Male)' },
        { value: 'yoZ06aMxZJJ28mfd3POQ', label: 'Dave (Male)' },
        { value: 'onwK4e9ZLuTAKqWW03F9', label: 'Fin (Male)' },
        { value: 'AcKYDwz6v5TcyRJdhGza', label: 'Grace (Female)' },
        { value: 'Hqze3u5S2If1m24xHZ3z', label: 'James (Male)' },
        { value: 'XB0fDUnXU5powFXDhCwa', label: 'Joseph (Male)' },
        { value: 'Zlb1dXrM653N07WRdFW3', label: 'Lily (Female)' },
        { value: 'pqHfZKP75CvOlQylNhV4', label: 'Michael (Male)' },
        { value: 'flq6f7yk4E4fJM5XTYuZ', label: 'Grace (Female)' },
        { value: 'pqHfZKP75CvOlQylNhV4', label: 'Nicole (Female)' },
        { value: 'yoZ06aMxZJJ28mfd3POQ', label: 'Sarah (Female)' },
        { value: 'XB0fDUnXU5powFXDhCwa', label: 'Sam (Male)' },
        { value: 'Zlb1dXrM653N07WRdFW3', label: 'Thomas (Male)' },
        { value: '4VZIsMPtgggwNg7OXbPY', label: 'James Gao (male)' },
        { value: 'Zn9Dr8ByqLL28MA9iEiR', label: 'Mia Chou (female)' },
        { value: 'cgSgspJ2msm6clMCkdW9', label: 'Jessica (female)' },
        { value: 'D9Thk1W7FRMgiOhy3zVI', label: 'Aaron - American Monotone Tech Narrator (male)' },
        { value: 'XZ8zM3TBDcQnbvfD1YDK', label: 'Agapi (female)' },
        { value: 'bFr1v73huN7dLcVKbWRD', label: 'Alain-Pro French HQ Premium Warm Calm Clear Voice Reader Shure SM7B (male)' },
        { value: 'YKUjKbMlejgvkOZlnnvt', label: 'Alejandro Ballesteros (male)' },
        { value: 'MZxV5lN3cv7hi1376O0m', label: 'Ana Dias (female)' },
        { value: 'wJqPPQ618aTW29mptyoc', label: 'Ana-Rita2 (female)' },
        { value: 'bYqmvVkXUBwLwYpGHGz3', label: 'Andrej (male)' },
        { value: '90ipbRoKi4CpHXvKVtl0', label: 'Anika - Customer Care Agent (female)' },
        { value: '02y4x5i9YrzYlFvGo1pp', label: 'Annabell - Professional and Inquisitive (female)' },
        { value: 'sx8pHRzXdQfuUYPGFK7X', label: 'Anna (female)' },
        { value: 'uFA9UGUhpwqGjVhz3lA2', label: 'Christine (female)' },
        { value: 'HDc7042zGcc1SdpT2m1U', label: 'Christophe Géradon (Belge) (male)' },
        { value: 'dhwafD61uVd8h85wAZSE', label: 'Denzel Authentic Jamaican Gangster (male)' },
        { value: 'MF4J4IDTRo0AxOO4dpFR', label: 'Devi - Clear Hindi pronunciation (female)' },
        { value: 'OAAjJsQDvpg3sVjiLgyl', label: 'Denisa Ozdobinski (female)' },
        { value: 'zwqMXWHsKBMIb9RPiWI0', label: 'Dom - deep warm British male voice (male)' },
        { value: 'gTbeQtAHxnyOpb7PcIo6', label: 'Dylan - confident (male)' },
        { value: 'iw7467blzNLEVe32avRC', label: 'Alcione (female)' },
        { value: '2BJW5coyhAzSr8STdHbE', label: 'Edward (male)' },
        { value: 'vFQACl5nAIV0owAavYxE', label: 'Ivan (male)' },
        { value: 'nDJIICjR9zfJExIFeSCN', label: 'Emmaline - young British girl (female)' },
        { value: 'gbTn1bmCvNgk0QEAVyfM', label: 'Enrique M. Nieto (male)' },
        { value: 'TzH1pxB1AEqPI2KZJ5MT', label: 'Faisal (male)' },
        { value: 'IJycFmUC4IMaRuOpVajx', label: 'German_Daniel (male)' },
        { value: 'wytO3xyllSDjJKHNkchr', label: 'GianP - Edu - clear & upbeat (male)' },
        { value: 'Yb9rQITgCX1VdXgAkbjM', label: 'Gioele Mediterraneo (male)' },
        { value: 'RZ9oBlQ97k7Ug7uU1Ij0', label: 'Giulia Pro - upbeat & clear (female)' },
        { value: 'kfAgTu73p0UPH0WkLC53', label: 'Greg (Male) - TV Promoter , Car Salesman (male)' },
        { value: 'M7ya1YbaeFaPXljg9BpK', label: 'Hannah Jayne (female)' },
        { value: 'HBwtuRG9VQfaoE2YVMYf', label: 'Himanshu Kumar (male)' },
        { value: 'zZp9y0VzL7J3DmI1Z0U6', label: 'Hrp - informational (male)' },
        { value: 'C1DBnkwmDIzoLOPlBvSg', label: 'Ignacius (male)' },
        { value: 's7Z6uboUuE4Nd8Q2nye6', label: 'HansC (male)' },
        { value: 'DbbNuBL7lf62XwY7arQb', label: 'Hugo from Paris (male)' },
        { value: 'BRXqZ6YAQpe2chRY9PCd', label: 'Jerry - Energetic and Upbeat (male)' },
        { value: 'rKEZ4zwQvgkCKHp8yR8n', label: 'Jessica - Cali girl (female)' },
        { value: 'wzDoTGd1zUgOmuNJb7aU', label: 'Jessica - Cali girl (female)' },
        { value: 'xKh2Ei1EjI34eYNsEWzd', label: 'Jessica - Cali girl (female)' },
        { value: '9EU0h6CVtEDS6vriwwq5', label: 'Julieta - Young, Gentle & Calm (female)' },
        { value: 'SViKDEbKzJqnyyQeoxow', label: 'Julius Professional (male)' },
        { value: 'e6OiUVixGLmvtdn2GJYE', label: 'Jonas, calm & informative Swedish voice (male)' },
        { value: 'Nbttze9nhGhK1czblc6j', label: 'JuniorDT (male)' },
        { value: '8NOqHwer6AD8mGkiPfkf', label: 'Nala - African Female (female)' },
        { value: '2EiwWnXFnvU5JabPnv8n', label: 'Clyde (male)' },
        { value: 'bVMeCyTHy58xNoL34h3p', label: 'Jeremy (male)' },
        { value: 'Zlb1dXrM653N07WRdFW3', label: 'Joseph (male)' },
        { value: 'fCqNx624ZlenYx5PXk6M', label: 'Aimee (female)' },
        { value: 'pMsXgVXv3BLzUgSXRplE', label: 'Serena (female)' },
        { value: 'mOYuoC0AAGM3erLKflvc', label: 'Nanay Pilar - Filipino Voice (female)' },
        { value: 'PrmfKloobWGGLoyMJEHx', label: 'Ava - Old And Deep  (female)' },
        { value: 'AZnzlk1XvdvUeBnXmlld', label: 'Domi (female)' },
        { value: 'XTCVM18yEjPBfuDkkViC', label: 'Test Aaron 2 (male)' },
        { value: 'yDUXXKsu0jF5vdJnWAPU', label: 'Jake - Pro Studio Quality (male)' },
        { value: 'AVIlLDn2TVmdaDycgbo3', label: 'Eric (male)' },
        { value: 'qXguuHzwYUAXqe4Qq1CR', label: 'Benji  (male)' },
        { value: 'XVoJzougZyL8yUp6ITsZ', label: 'Salman (male)' },
        { value: 'DbSecejUVkMJPcrM407R', label: 'Sarah (female)' },
        { value: 't0jbNlBVZ17f02VDIeMI', label: 'Jessie (male)' },
        { value: 'CYw3kZ02Hs0563khs1Fj', label: 'Dave (male)' },
        { value: 'hWnML2XRpykt4MG3bS1i', label: 'Adam - Action Movie Recap Narrator (male)' },
        { value: 'unROvA6wrI5G5MtDhPFJ', label: 'Ashod  (male)' },
        { value: 'dGZOPnBPB65AKnGqsIW8', label: 'Astor (female)' },
        { value: 'Tgt516HUMdlubfHNNDTH', label: 'Brett S. (male)' },
        { value: 'z9fAnlkpzviPz146aGWa', label: 'Glinda (female)' },
        { value: 'Tw2LVqLUUWkxqrCfFOpw', label: 'Cole - Gritty-Rough-Strong (male)' },
        { value: 'yoZ06aMxZJJ28mfd3POQ', label: 'Sam (male)' },
        { value: 'wmVUmV0V5WKDwDkjdgZT', label: 'Kevin Dufraisse (male)' },
        { value: 'edp3dSZHBzH1qX2Kl28e', label: 'Emma - calm and posh (female)' },
        { value: '7wZG7UyB12X3ndKa4uqi', label: 'Eric (male)' },
        { value: 'dlGxemPxFMTY7iXagmOj', label: 'Fernando Martinez (male)' },
        { value: 'QblstfKg363M9dHO7soi', label: 'Garaudi (male)' },
        { value: 'AkxsbbnvuvZZmyLQ40sj', label: 'Jessica (female)' },
        { value: '1SM7GgM6IMuvQlz2BwM3', label: 'Mark - ConvoAI (male)' },
        { value: 'YCWtGUjb6aTzXFqTcOem', label: 'Eden (female)' },
        { value: 'tvWD4i07Hg5L4uEvbxYV', label: 'Mary (female)' },
        { value: '8PCccElp0PQGRfTFCu0p', label: 'Aleksandr Petrov (male)' },
        { value: 'D7fO4LMKxU3UYXGDpTnA', label: 'Maxi_Argames (male)' },
        { value: 'sfmm6XI0fFTFoYgDQarA', label: 'MelonBread Voice (male)' },
        { value: 'EmspiS7CSUabPeqBcrAP', label: 'Mikołaj (male)' },
        { value: 'LLEUnU5vlkaEV6dSdkOl', label: 'Kim (female)' },
        { value: 'nm1ZvXYfIcWIwMXCKoBV', label: 'Paxton Ballard (male)' },
        { value: 'FjJJxwBrv1I5sk34AdgP', label: 'Rayyan (male)' },
        { value: 'rCmVtv8cYU60uhlsOo1M', label: 'Ana-Rita1 (female)' },
        { value: 'C8Qbw8pAs2Q6xnmJACLv', label: 'Ani Egea (female)' },
        { value: 'o86w79lw8Y208S2HjL2M', label: 'Benjamin - The Frenchy Guy (male)' },
        { value: 'bmAn0TLASQN7ctGBMHgN', label: 'Bennett (female)' },
        { value: 'LruHrtVF6PSyGItzMNHS', label: 'Benjamin - Deep, Warm, Calming (male)' },
        { value: 'ukupJ4zdf9bo1Py6MiO6', label: 'Beto - Latin American Spanish (male)' },
        { value: 'onwK4e9ZLuTAKqWW03F9', label: 'Daniel (male)' },
        { value: 'nPczCjzI2devNBz1zQrb', label: 'Brian (male)' },
        { value: 'Hmz0MdhDqv9vPpSMfDkh', label: 'Bobby indian male (male)' },
        { value: 'C6rJhyzI76mRm09eDUBF', label: 'Brian - deep narrator (male)' },
        { value: 'xgnMn9p1V1XVuxuyuuMC', label: 'Brianna- Excited and friendly (female)' },
        { value: 'YEkUdc7PezGaXaRslSHB', label: 'Bubba Marshal (male)' },
        { value: 'aHCytOTnUOgfGPn5n89j', label: 'Busy Bea - Corporate  (female)' },
        { value: 'zIusdI28yOZPwIBus0aI', label: 'Bé Hồng Ân (female)' },
        { value: 'sJ0xHHPzz1LOio4QNlB0', label: 'CAGLAYAN (female)' },
        { value: 'ZbPYBUkPr8zRzs9waW8s', label: 'wise-woman (female)' },
        { value: 'LjKPkQHpXCsWoy7Pjq4U', label: 'Alice (female)' },
        { value: 'PMLpVjMbDfOl1OqgTb0Y', label: 'Cas (male)' },
        { value: 'XB0fDUnXU5powFXDhCwa', label: 'Charlotte (female)' },
        { value: 'aqXKinCxkMOvW6f3qU8l', label: 'Casual Cal (male)' },
        { value: 'iP95p4xoKVk53GoZ742B', label: 'Chris (male)' },
        { value: 'mA9QlbG029ANYKh6JeNt', label: 'Catherine Rose (female)' },
        { value: 'FGY2WhTYpPnrIDTdsKH5', label: 'Laura (female)' },
        { value: '54Cze5LrTSyLgbO6Fhlc', label: 'Cat - Droll and Dry (female)' },
        { value: 'LQboqQKiOAfFvYtOK9H4', label: 'Charlie - gentle knowledgeable old voice    (male)' },
        { value: 'NHRgOEwqx5WZNClv5sat', label: 'Chelsea -  Friendly, approachable, confident (female)' },
        { value: 'qJT4OuZyfpn7QbUnrLln', label: 'Charlotte  (female)' },
        { value: '86ZLAUcyPNBrbdJKn3u6', label: 'Chris Professional Audio (male)' },
        { value: '6mBnRhL0dHfQ4ypWLcIi', label: 'Christian Brison - Nieuws Stijl (male)' },
        { value: 'CAYdOeRRe8sLTuBjQ1ht', label: 'Clon Diever Muñoz (male)' },
        { value: 'MGnihriF5kUNUiVoIdz1', label: 'Connor Brown (male)' },
        { value: '6hfb8itl0CXl6ZA7WVIA', label: 'Cristian Medina (realistic) (male)' },
        { value: 'KTPVrSVAEUSJRClDzBw7', label: 'Cowboy Bob™ // VF (male)' },
        { value: 'aOcS60CY8CoaVaZfqqb5', label: 'Cowboy - Hugh man (male)' },
        { value: 'IpCcRCVYm2nsZJjBFn4H', label: 'Cristiano - American English with Portuguese Accent (male)' },
        { value: 'nHNZWlqUWtEKPr3hhFQP', label: 'Daiane Candido (female)' },
        { value: 'Dme3o25EiC1DfrBQd73f', label: 'Aggie (female)' },
        { value: 'Z55vjGJIfg7PlYv2c1k6', label: 'Daksh - Conversational, Delhi Accent (male)' },
        { value: '3eeY9rFz1akxkQTWoYXs', label: 'Daisy - Young Scottish Female (female)' },
        { value: 'P7x743VjyZEOihNNygQ9', label: 'Dakota H (female)' },
        { value: '7QQzpAyzlKTVrRzQJmTE', label: 'DANI ESPAÑOL (male)' },
        { value: 'ufcWUsHMJ6cZeQ1nxFGt', label: 'Dan - Enthusiastic Scholar (male)' },
        { value: 'Xb7hH8MSUJpSbSDYk0k2', label: 'Alice (female)' },
        { value: 'cjVigY5qzO86Huf0OWal', label: 'Eric (male)' },
        { value: 'ZxeM4498ujGNHYhQXtLS', label: 'Davi (male)' },
        { value: 'jvcMcno3QtjOzGtfpjoI', label: 'David - British Documentary (male)' },
        { value: 'nF7t9cuYo0u3kuVI9q4B', label: 'Dana – Engaging Confident German Female (female)' },
        { value: 'v9LgF91V36LGgbLX3iHW', label: 'David - American Narrator (male)' },
        { value: 'bRpWukXwO2RH5TziHDQL', label: 'Jin (male)' },
        { value: 'TC46yNhl5pQS6ekc0ZzW', label: 'Paul - sped-up(September) (male)' },
        { value: 'BfDbhCUVGzNgO4WXDRdy', label: 'Donny - Real New Yorker (male)' },
        { value: 'mnEe2Jhwlupp6oZEDi3k', label: 'Elif (female)' },
        { value: 'dMyQqiVXTU80dDl2eNK8', label: 'Eryn Natural Conversational (female)' },
        { value: 'dvcWDtDpjMuyqdgVg3Hu', label: 'Evie - Friendly  (female)' },
        { value: 'JM2A9JbRp8XUJ7bdCXJc', label: 'Fernanda olea 1 (female)' },
        { value: 'D9MdulIxfrCUUJcGNQon', label: 'Jhenny 3 - Meditation & Affirmations (female)' },
        { value: 'XmoCtjPCefjeLDu0eMSl', label: 'LALO (argentino sereno) (male)' },
        { value: 'VQWIG7jHNSEv826utbm8', label: 'Mike Henry (male)' },
        { value: '8SKmtcPfPSqEllqMebBk', label: 'Misti - English Technology Virtual Training Teacher (female)' },
        { value: 'jkSXBeN4g5pNelNQ3YWw', label: 'Molly (female)' },
        { value: '2zRM7PkgwBPiau2jvVXc', label: 'Monika Sogam (female)' },
        { value: 'iJvD32aW89RhjCC00q0m', label: 'Natasia - Snarky and Mature (female)' },
        { value: 'YO5U6V757mqh3xnbe4XE', label: 'Pavel Meditation VOICE (male)' },
        { value: 'cC1MV58eC4jgoctdxF33', label: 'Sascha (male)' },
        { value: '2BHdCav31gsQx0y0Af0C', label: 'Serena (female)' },
        { value: 'vWs6DV3PkGKMP80V1Hv6', label: 'Dilek Iyigun (female)' },
        { value: 'dHdIIFZMLzs6XfsGtmIP', label: 'Sheila España (female)' },
        { value: 'sV4fwMmnVHw2nJJcje10', label: 'Jane (female)' },
        { value: 'VKI3eJDbOrmY9bpCd2w8', label: 'testing_audio (male)' },
        { value: 'xctasy8XvGp2cVO9HL9k', label: 'Allison - millennial (female)' },
        { value: 'tMgsWfYUtv6CJS0C5iKU', label: 'Aria (female)' },
        { value: 'blxHPCXhpXOsc7mCKk0P', label: 'Artem K (male)' },
        { value: 'Gf1KYedBUv2F4rCJhVFJ', label: 'Boston Bob - Wicked-Pissah (male)' },
        { value: 'yaEGPh9UGwCWQSEACoBP', label: 'Bryan (male)' },
        { value: 'suMMgpGbVcnihP1CcgFS', label: 'Emily - Young Irish lady (female)' },
        { value: '372NsdHr6qutUh2JE8DJ', label: 'Géza B. (male)' },
        { value: 'gyYsiReJHZ1ezjr0NdZ1', label: 'Krzysztof PL (male)' },
        { value: 'X69aMGx8u7YHtScNLx9R', label: 'Mang Jose V2 (male)' },
        { value: 'jECSILWxg8xmZ7q2QNZ5', label: 'Priya (female)' },
        { value: 'gK1LObx9dyXDky5HXXhi', label: 'Allen Thomas (male)' },
        { value: 'ly9KhF8qwAd2anTHGzHx', label: 'Chris (male)' },
        { value: 'EXdHO4EOBGhUgn4DgCBt', label: 'Chuck (male)' },
        { value: 'FVQMzxJGPUBtfz1Azdoy', label: 'Danielle - Canadian Narrator (female)' },
        { value: 'kBgEvFfs5Y9ynCZaHDOm', label: 'Gabriele (male)' },
        { value: 'Dslrhjl3ZpzrctukrQSN', label: 'Hey Its Brad - Clear Narrator for Documentary (male)' },
        { value: 'UlwxMDtxqMDYmG6pk2q6', label: 'Luca Brasi Gentile (male)' },
        { value: 'qj0G3LGGWtmBYX3gzAO7', label: 'Ödül  (female)' },
        { value: 'c1An0BcfdBgMtEqajijL', label: 'Berke Yağız Hınç - Universal Tone HQ (male)' },
        { value: 'RCSF5YgDtAhZXpNZfGek', label: 'Andréa (female)' },
        { value: 'jBpfuIE2acCO8z3wKNLl', label: 'Gigi (female)' },
        { value: 'xPVEa1fRos3Rlvw7i1XC', label: 'Arul (female)' },
        { value: 'kqVT88a5QfII1HNAEPTJ', label: 'Declan Sage - Wise, Deliberate, Captivating (male)' },
        { value: 'Jvx0SZHtelVH4bP2bPhY', label: 'Maggie - Young Professional (female)' },
        { value: 'MF3mGyEYCl7XYWbV9V6O', label: 'Elli (female)' },
        { value: 'OW3B7Vuy84v36YBLGv5N', label: 'Fırat Öztürk (male)' },
        { value: 'VDWBRvOTjy2gFBaEo68H', label: 'Fr (male)' },
        { value: 'Gp9tE4wM5inXdXq8sCgP', label: 'Xavier Fok (male)' },
        { value: 'zoHYyRSu24lsAjrjXkxu', label: 'Susanna Rutkai (female)' },
        { value: 'Uyx98Ek4uMNmWN7E28CD', label: 'Aakash Aryan - Conversational Voice (male)' },
        { value: 'tUGS6AkOqpB4R8nb1DTU', label: 'Alessandro Devigus (male)' },
        { value: 'kdmDKE6EkgrWrrykO9Qt', label: 'Alexandra - Conversational and Real (female)' },
        { value: 'S98OhkhaxeAKHEbhoLi7', label: 'Andrei (male)' },
        { value: 'PT4nqlKZfc06VW1BuClj', label: 'Angela  (female)' },
        { value: 'FUfBrNit0NNZAwb58KWH', label: 'Angela - Conversational (female)' },
        { value: 'BIvP0GN1cAtSRTxNHnWS', label: 'Ellen (female)' },
        { value: '3OArekHEkHv5XvmZirVD', label: 'Christoffer Satu (male)' },
        { value: 'usTmJvQOCyW3nRcZ8OEo', label: 'Dante - Castilian Spanish (male)' },
        { value: '13auRs13gEKuqxX054G2', label: 'Gustavo Barros (male)' },
        { value: 'A9ATTqUUQ6GHu0coCz8t', label: 'Hamid (male)' },
        { value: 'oQV06a7Gn8pbCJh5DXcO', label: 'Archer - Narration (male)' },
        { value: 'CAEve7xpu0AvVWiKm2px', label: 'Javier España (male)' },
        { value: 'b40q94MErxP9aasHjJ2w', label: 'Jeremie (male)' },
        { value: 'EP3g1wv2wIp7Hc96Sf4l', label: 'John (male)' },
        { value: '7u8qsX4HQsSHJ0f8xsQZ', label: 'João Pedro (male)' },
        { value: 'KDmYQaYG1Bvcoe7aL9Pn', label: 'Reese - Professional VO (male)' },
        { value: 'fQ74DTbwd8TiAJFxu9v8', label: 'Kimber K (female)' },
        { value: 'pvxGJdhknm00gMyYHtET', label: 'Kota (female)' },
        { value: 'FM3ChNOHGKKULNhmx5m2', label: 'Lola Jaen (female)' },
        { value: 'jtGikLBTNEkFD4bfjus6', label: 'Mallory (female)' },
        { value: 'H4elJChr6VJwJc9ZsxAQ', label: 'Marcèles 2025 v2 (male)' },
        { value: 'S7L0uJpUCUDUktI3y5cw', label: 'Marco Professionale (male)' },
        { value: '5d9jEFwkzN2grNaI7bw1', label: 'Maxime Lavaud - French young man REMASTERED (male)' },
        { value: 'AxblkYAT25eyLk0hDMy8', label: 'Max - deep voice (male)' },
        { value: 'MiueK1FXuZTCItgbQwPu', label: 'Maya - Young Australian Female (female)' },
        { value: 'gIvqZOUTPy4VbQwwKu9Z', label: 'Milan Diekstra (male)' },
        { value: 'GYzIdoKkRyANjBvkKYfO', label: 'Miss French Papote (female)' },
        { value: 'NbxPoSbxk2KEIE26f6NL', label: 'Muge Dawn (female)' },
        { value: 'lTvtSobl0SaWDikyBCB6', label: 'Nikky - soft and sweet (female)' },
        { value: 'ANHrhmaFeVN0QJaa0PhL', label: 'Petra Vlaams (female)' },
        { value: 'pfHF7CDbPKOQaeSpVy6d', label: 'Ricky The K (male)' },
        { value: '5pQ2oaKwfSnEC8Tk0ITe', label: 'Rose (female)' },
        { value: 'ck9HoVCLp6afqYbgxSAA', label: 'Paul (male)' },
        { value: 'KHCvMklQZZo0O30ERnVn', label: 'Sara Martin 1 (female)' },
        { value: 'b5RPB35vTODb3BEmR3Fc', label: 'Serein - Casual American Friend (female)' },
        { value: 'Og6C5DgTHIScy85Fgh41', label: 'Claudia (female)' },
        { value: 'ZMU08EK8uIPC9TTjO4ML', label: 'Young brit (female)' },
        { value: 'NHv5TpkohJlOhwlTCzJk', label: '📣 Pawel TV™️ - 🔊 High Quality ✔️- CZECH (male)' },
        { value: '5jTLciGr7JGMshpxjhek', label: '🎙️ Diego - Deep, Resonant & Multilingual (male)' },
        { value: 'F9w7aaEjfT09qV89OdY8', label: 'Voce Minatore Audiolibro (male)' },
        { value: 'YNOujSUmHtgN6anjqXPf', label: 'Victor Power - Ebooks (male)' },
        { value: 'dxhwlBCxCrnzRlP4wDeE', label: 'Arjun - Soothing Audiobook Narration (male)' },
        { value: 'J1T7JICxvIWZSrp2n31r', label: 'Adam - Calm, Smart (male)' },
        { value: '1THll2MhJjluQYaSQxDr', label: 'Sanchez (male)' },
        { value: '45bOcRRMXeR9ihSPLJ11', label: 'Ariel (female)' },
        { value: 'o3Pmyfc3Ez1s2CJKuwJf', label: 'Ben van Praag (male)' },
        { value: 'h3aQ5g69oxB0wpernpfx', label: 'Bogdan Alexandru (male)' },
        { value: 'GdUwr3tVJwSb22ROvLCr', label: 'Jukka Uusitalo - Finnish (male)' },
        { value: '4NejU5DwQjevnR6mh3mb', label: 'Ivanna - Girl Next Door (female)' },
        { value: 'GoEy5CmodqJy0T9AxjLk', label: 'Mélanie (female)' },
        { value: 'dDU5VfWXOm9eAwl9oqA1', label: 'Om Tobi NOBAR (male)' },
        { value: 'xi3rF0t7dg7uN2M0WUhr', label: 'Yuna (female)' },
        { value: 'sarah', label: 'Sarah - Fun, Realistic Voice for Kids & Cartoons (neutral)' },
        { value: 'XBDAUT8ybuJTTCoOLSUj', label: 'MC Anh Đức (male)' },
        { value: 'O7RnF5aNrnDdDZdG7kki', label: 'Isaiah - Male Soft Quiet Voice (male)' },
        { value: 'GgmlugwQ4LYXBbEXENWm', label: 'Maya | Young & Calming  (female)' },
        { value: 'sIak7pFapfSLCfctxdOu', label: 'Clarice – Customer Care: Smooth, Calm and Human-like (female)' },
        { value: 'fIdR6vJWGZypitdCsqgC', label: 'Phillip Howe (male)' },
        { value: 'A9evEp8yGjv4c3WsIKuY', label: 'Ralf Eisend (male)' },
        { value: 'iFviksYEY11Iy0Z6Phmz', label: 'Sebastian Thomas - Male (male)' },
        { value: 'wLHKH5PlbXh3SONbJgrj', label: 'Son-ia-online (female)' },
        { value: 'kL06KYMvPY56NluIQ72m', label: 'Varsha - Indian Storyteller (female)' },
        { value: '3QXPfzW1QCV55kXilPul', label: 'Willie1234 (male)' },
        { value: 'kzrsjZhHCumKqmkJl486', label: 'Юлиян (male)' },
        { value: 'WXmGNP5rvPWuO0RYTqfP', label: 'Abyasa (male)' },
        { value: 'EVHgV4ZlNeOu3FwfRmkG', label: 'Berk - Full Range & Natural Narration (male)' },
        { value: '6rr4jpS124uCLNtgVdAk', label: 'Chris Heyez (male)' }
      ],
      azure: [
        { value: 'en-US-AriaNeural', label: 'Aria (Female, American)' },
        { value: 'en-US-DavisNeural', label: 'Davis (Male, American)' },
        { value: 'en-US-JennyNeural', label: 'Jenny (Female, American)' },
        { value: 'en-US-GuyNeural', label: 'Guy (Male, American)' },
        { value: 'en-US-AmberNeural', label: 'Amber (Female, American)' },
        { value: 'en-US-ChristopherNeural', label: 'Christopher (Male, American)' }
      ],
      google: [
        { value: 'en-US-Wavenet-A', label: 'Wavenet A (Male, American)' },
        { value: 'en-US-Wavenet-B', label: 'Wavenet B (Male, American)' },
        { value: 'en-US-Wavenet-C', label: 'Wavenet C (Female, American)' },
        { value: 'en-US-Wavenet-D', label: 'Wavenet D (Male, American)' },
        { value: 'en-US-Wavenet-E', label: 'Wavenet E (Female, American)' },
        { value: 'en-US-Wavenet-F', label: 'Wavenet F (Female, American)' }
      ],
      aws: [
        { value: 'Joanna', label: 'Joanna (Female, American)' },
        { value: 'Matthew', label: 'Matthew (Male, American)' },
        { value: 'Amy', label: 'Amy (Female, British)' },
        { value: 'Emma', label: 'Emma (Female, British)' },
        { value: 'Brian', label: 'Brian (Male, British)' },
        { value: 'Arthur', label: 'Arthur (Male, British)' }
      ],
      openai: [
        { value: 'alloy', label: 'Alloy (Neutral)' },
        { value: 'echo', label: 'Echo (Male)' },
        { value: 'fable', label: 'Fable (Male)' },
        { value: 'onyx', label: 'Onyx (Male)' },
        { value: 'nova', label: 'Nova (Female)' },
        { value: 'shimmer', label: 'Shimmer (Female)' }
      ],
      playht: [
        { value: 'jennifer', label: 'Jennifer (Female, American)' },
        { value: 'michael', label: 'Michael (Male, American)' },
        { value: 'sarah', label: 'Sarah (Female, American)' },
        { value: 'david', label: 'David (Male, American)' },
        { value: 'lisa', label: 'Lisa (Female, American)' },
        { value: 'john', label: 'John (Male, American)' }
      ],
      deepgram: [
        { value: 'aura-asteria-en', label: 'Asteria (Female, American)' },
        { value: 'aura-luna-en', label: 'Luna (Female, American)' },
        { value: 'aura-stella-en', label: 'Stella (Female, American)' },
        { value: 'aura-athena-en', label: 'Athena (Female, American)' },
        { value: 'aura-orion-en', label: 'Orion (Male, American)' },
        { value: 'aura-arcas-en', label: 'Arcas (Male, American)' }
      ],
      rime: [
        { value: 'rime_voice_1', label: 'Voice 1 (Female, American)' },
        { value: 'rime_voice_2', label: 'Voice 2 (Male, American)' },
        { value: 'rime_voice_3', label: 'Voice 3 (Female, British)' },
        { value: 'rime_voice_4', label: 'Voice 4 (Male, British)' }
      ],
      custom: []
    }

    // Model options for each provider
    const modelOptions = {
      '11labs': [
        { value: 'eleven_multilingual_v2', label: 'Eleven Multilingual v2' },
        { value: 'eleven_turbo_v2', label: 'Eleven Turbo v2' },
        { value: 'eleven_turbo_v2_5', label: 'Eleven Turbo v2.5' },
        { value: 'eleven_flash_v2', label: 'Eleven Flash v2' },
        { value: 'eleven_flash_v2_5', label: 'Eleven Flash v2.5' },
        { value: 'eleven_monolingual_v1', label: 'Eleven English v1' }
      ],
      openai: [
        { value: 'tts-1', label: 'TTS-1 (Fast)' },
        { value: 'tts-1-hd', label: 'TTS-1-HD (High Quality)' }
      ],
      playht: [
        { value: 'standard', label: 'Standard' },
        { value: 'premium', label: 'Premium' },
        { value: 'turbo', label: 'Turbo' }
      ]
    }

    // Get available voices for current provider
    const getAvailableVoices = () => {
      const voices = voiceOptions[voiceConfig.value.provider] || []
      return voices.sort((a, b) => a.label.localeCompare(b.label))
    }

    // Get filtered voices based on search query
    const getFilteredVoices = () => {
      const voices = getAvailableVoices()
      if (!voiceSearchQuery.value.trim()) {
        return voices
      }
      return voices.filter(voice => 
        voice.label.toLowerCase().includes(voiceSearchQuery.value.toLowerCase())
      )
    }

    // Get available models for current provider
    const getAvailableModels = () => {
      return modelOptions[voiceConfig.value.provider] || []
    }

    // Check if provider supports speed control
    const supportsSpeed = () => {
      const speedSupportedProviders = ['vapi', 'elevenlabs', 'openai', 'playht', 'deepgram', 'rime']
      return speedSupportedProviders.includes(voiceConfig.value.provider)
    }

    // Provider change handler
    const onProviderChange = () => {
      const voices = getAvailableVoices()
      const models = getAvailableModels()
      
      // Reset to first available voice
      voiceConfig.value.voiceId = voices.length > 0 ? voices[0].value : ''
      
      // Reset to first available model
      voiceConfig.value.model = models.length > 0 ? models[0].value : ''
      
      // Reset speed to default
      voiceConfig.value.speed = 1
    }

    // Watch for changes and emit updates (but not when updating from props)
    watch(voiceConfig, (newConfig) => {
      // Only emit if this change didn't come from props
      if (!isUpdatingFromProps.value) {
        emit('update:voice', newConfig)
      }
    }, { deep: true })

    // Initialize with props
    if (props.voice && Object.keys(props.voice).length > 0) {
      isUpdatingFromProps.value = true
      voiceConfig.value = { ...voiceConfig.value, ...props.voice }
      // Reset flag after a tick to allow the emit watcher to work again
      setTimeout(() => {
        isUpdatingFromProps.value = false
      }, 0)
    }

    return {
      voiceConfig,
      voiceSearchQuery,
      getAvailableVoices,
      getFilteredVoices,
      getAvailableModels,
      supportsSpeed,
      onProviderChange
    }
  }
}
</script>

<style scoped>
.slider::-webkit-slider-thumb {
  appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #10b981;
  cursor: pointer;
}

.slider::-moz-range-thumb {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #10b981;
  cursor: pointer;
  border: none;
}
</style>
