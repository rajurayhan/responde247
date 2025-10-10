<template>
  <div class="space-y-6">
    <!-- Analysis Plan -->
    <div class="space-y-6">
      <!-- Summary Section -->
      <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
        <div class="flex items-center mb-4">
          <input
            v-model="analysisConfig.summary"
            type="checkbox"
            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
          />
          <h3 class="text-lg font-medium text-gray-900 ml-3">Summary</h3>
        </div>
        
        <p class="text-sm text-gray-600 mb-4">This is the prompt that's used to summarize the call. The output is stored in call.analysis.summary. You can also find the summary in the Call Logs Page.</p>
        
        <div v-if="analysisConfig.summary" class="space-y-4">
          <!-- Prompt Section -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prompt</label>
            <textarea
              v-model="analysisConfig.summaryPrompt"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
              placeholder="You are an expert note-taker. You will be given a transcript of a call. Summarize the call in 2-3 sentences, if applicable."
            ></textarea>
          </div>
          
          <!-- Summary Request Timeout -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Summary request timeout in seconds.</label>
            <div class="flex items-center space-x-4">
              <input
                v-model.number="analysisConfig.summaryTimeout"
                type="range"
                min="1"
                max="60"
                class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
              />
              <div class="flex space-x-2 text-sm text-gray-500">
                <span>1 (sec)</span>
                <span class="font-medium text-gray-900 bg-gray-200 px-2 py-1 rounded">{{ analysisConfig.summaryTimeout }}</span>
                <span>60 (sec)</span>
              </div>
            </div>
          </div>
          
          <!-- Minimum Number of Messages -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Minimum number of messages to trigger analysis.</label>
            <div class="flex items-center space-x-4">
              <input
                v-model.number="analysisConfig.minMessages"
                type="range"
                min="0"
                max="10"
                class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
              />
              <div class="flex space-x-2 text-sm text-gray-500">
                <span>0</span>
                <span class="font-medium text-gray-900 bg-gray-200 px-2 py-1 rounded">{{ analysisConfig.minMessages }}</span>
                <span>10</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Evaluation Section -->
      <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
        <div class="flex items-center mb-4">
          <input
            v-model="analysisConfig.successEvaluation"
            type="checkbox"
            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
          />
          <h3 class="text-lg font-medium text-gray-900 ml-3">Success Evaluation</h3>
        </div>
        
        <p class="text-sm text-gray-600 mb-4">Evaluate if your call was successful. You can use Rubric standalone or in combination with Success Evaluation Prompt. If both are provided, they are concatenated into appropriate instructions.</p>
        
        <div v-if="analysisConfig.successEvaluation" class="space-y-6">
          <!-- Success Evaluation Prompt -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-2">Prompt</h4>
            <p class="text-sm text-gray-600 mb-2">This is the prompt that's used to evaluate if the call was successful.</p>
            <textarea
              v-model="analysisConfig.successEvaluationPrompt"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
              placeholder="You are an expert call evaluator. You will be given a transcript of a call and the system prompt of the AI participant. Determine if the call was successful based on the objectives inferred from the system prompt."
            ></textarea>
          </div>

          <!-- Success Evaluation Rubric -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-2">Success Evaluation Rubric</h4>
            <p class="text-sm text-gray-600 mb-4">This enforces the rubric of the evaluation upon the Success Evaluation.</p>
            
            <!-- Checklist Section -->
            <div class="border border-gray-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-4">
                <h5 class="text-sm font-medium text-gray-900">Checklist</h5>
                <button
                  @click="toggleChecklist"
                  type="button"
                  class="text-gray-400 hover:text-gray-600"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                  </svg>
                </button>
              </div>
              <p class="text-xs text-gray-500 mb-4">A checklist of criteria and their status.</p>
              
              <div v-if="showChecklist" class="space-y-3">
                <div v-for="(criterion, index) in analysisConfig.checklist" :key="index" class="flex items-center space-x-3">
                  <input
                    v-model="criterion.checked"
                    type="checkbox"
                    class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                  />
                  <input
                    v-model="criterion.text"
                    type="text"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    :placeholder="`Criterion ${index + 1}`"
                  />
                  <button
                    @click="removeCriterion(index)"
                    type="button"
                    class="text-red-500 hover:text-red-700"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
                <button
                  @click="addCriterion"
                  type="button"
                  class="w-full px-3 py-2 border-2 border-dashed border-gray-300 rounded-md text-gray-600 hover:border-green-500 hover:text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                  + Add Criterion
                </button>
              </div>
            </div>
          </div>

          <!-- Success Evaluation Timeout -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Success evaluation request timeout in seconds.</label>
            <div class="flex items-center space-x-4">
              <input
                v-model.number="analysisConfig.successEvaluationTimeout"
                type="range"
                min="1"
                max="60"
                class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
              />
              <div class="flex space-x-2 text-sm text-gray-500">
                <span>1 (sec)</span>
                <span class="font-medium text-gray-900 bg-gray-200 px-2 py-1 rounded">{{ analysisConfig.successEvaluationTimeout }}</span>
                <span>60 (sec)</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Structured Data Section -->
      <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
        <div class="flex items-center mb-4">
          <input
            v-model="analysisConfig.structuredData"
            type="checkbox"
            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
          />
          <h3 class="text-lg font-medium text-gray-900 ml-3">Structured Data</h3>
        </div>
        
        <p class="text-sm text-gray-600 mb-4">Extract structured data from call conversation. You can use Data Schema standalone or in combination with Structured Data Prompt. If both are provided, they are concatenated into appropriate instructions.</p>
        
        <div v-if="analysisConfig.structuredData" class="space-y-6">
          <!-- Structured Data Prompt -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-2">Prompt</h4>
            <p class="text-sm text-gray-600 mb-2">This is the prompt that's used to extract structured data from the call.</p>
            <textarea
              v-model="analysisConfig.structuredDataPrompt"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
              placeholder="You will be given a transcript of a call and the system prompt of the AI participant. Extract structured data from the call based on the following schema..."
            ></textarea>
          </div>

          <!-- Structured Data Timeout -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Structured data request timeout in seconds.</label>
            <div class="flex items-center space-x-4">
              <input
                v-model.number="analysisConfig.structuredDataTimeout"
                type="range"
                min="1"
                max="60"
                class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
              />
              <div class="flex space-x-2 text-sm text-gray-500">
                <span>1 (sec)</span>
                <span class="font-medium text-gray-900 bg-gray-200 px-2 py-1 rounded">{{ analysisConfig.structuredDataTimeout }}</span>
                <span>60 (sec)</span>
              </div>
            </div>
          </div>

          <!-- Data Schema Properties -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-4">Data Schema Properties</h4>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <!-- Header -->
              <div class="bg-gray-100 px-4 py-3 grid grid-cols-12 gap-4 text-sm font-medium text-gray-700">
                <div class="col-span-4">Name</div>
                <div class="col-span-3">Type</div>
                <div class="col-span-3">Required</div>
                <div class="col-span-2">Actions</div>
              </div>
              
              <!-- Properties List -->
              <div v-if="analysisConfig.dataSchema.length === 0" class="p-8 text-center text-gray-500">
                <p>No properties defined. Click "Add Property" to get started.</p>
              </div>
              
              <div v-for="(property, index) in analysisConfig.dataSchema" :key="index" class="border-t border-gray-200 px-4 py-3 grid grid-cols-12 gap-4 items-center">
                <!-- Name -->
                <div class="col-span-4">
                  <input
                    v-model="property.name"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="Property name"
                  />
                  <button
                    v-if="!property.showDescription"
                    @click="property.showDescription = true"
                    class="text-xs text-blue-600 hover:text-blue-800 mt-1"
                  >
                    Add Description
                  </button>
                  <div v-if="property.showDescription" class="mt-2">
                    <textarea
                      v-model="property.description"
                      rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                      placeholder="Property description"
                    ></textarea>
                  </div>
                </div>
                
                <!-- Type -->
                <div class="col-span-3">
                  <select
                    v-model="property.type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  >
                    <option value="string">String</option>
                    <option value="number">Number</option>
                    <option value="boolean">Boolean</option>
                    <option value="array">Array</option>
                    <option value="object">Object</option>
                  </select>
                  <div class="mt-2">
                    <label class="flex items-center">
                      <input
                        v-model="property.isEnum"
                        type="checkbox"
                        class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                      />
                      <span class="ml-2 text-sm text-gray-700">Is Enum</span>
                    </label>
                  </div>
                </div>
                
                <!-- Required -->
                <div class="col-span-3">
                  <label class="flex items-center">
                    <input
                      v-model="property.required"
                      type="checkbox"
                      class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                    />
                    <span class="ml-2 text-sm text-gray-700">Mark as required</span>
                  </label>
                </div>
                
                <!-- Actions -->
                <div class="col-span-2">
                  <button
                    @click="removeProperty(index)"
                    type="button"
                    class="text-red-500 hover:text-red-700 p-1"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Add Property Button -->
            <button
              @click="addProperty"
              type="button"
              class="mt-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
            >
              Add Property
            </button>
          </div>
        </div>
      </div>

      <!-- Other Analysis Options -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Other Analysis Features</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
            <input
              v-model="analysisConfig.sentiment"
              type="checkbox"
              class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
            />
            <div class="ml-3">
              <label class="text-sm font-medium text-gray-700">Sentiment</label>
              <p class="text-xs text-gray-500">Analyze conversation sentiment</p>
            </div>
          </div>
          
          <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
            <input
              v-model="analysisConfig.topics"
              type="checkbox"
              class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
            />
            <div class="ml-3">
              <label class="text-sm font-medium text-gray-700">Topics</label>
              <p class="text-xs text-gray-500">Extract key topics from conversations</p>
            </div>
          </div>
          
          <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
            <input
              v-model="analysisConfig.entities"
              type="checkbox"
              class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
            />
            <div class="ml-3">
              <label class="text-sm font-medium text-gray-700">Entities</label>
              <p class="text-xs text-gray-500">Identify named entities in conversations</p>
            </div>
          </div>
          
          <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
            <input
              v-model="analysisConfig.actionItems"
              type="checkbox"
              class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
            />
            <div class="ml-3">
              <label class="text-sm font-medium text-gray-700">Action Items</label>
              <p class="text-xs text-gray-500">Extract action items from conversations</p>
            </div>
          </div>
          
          <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
            <input
              v-model="analysisConfig.followUps"
              type="checkbox"
              class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
            />
            <div class="ml-3">
              <label class="text-sm font-medium text-gray-700">Follow-ups</label>
              <p class="text-xs text-gray-500">Identify follow-up requirements</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Artifact Plan -->
    <div>
      <h3 class="text-lg font-medium text-gray-900 mb-4">Artifact Plan</h3>
      <p class="text-sm text-gray-600 mb-4">Configure which artifacts to generate and store during assistant's calls. Artifacts are stored in `call.artifact`.</p>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
          <input
            v-model="artifactConfig.recording"
            type="checkbox"
            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
          />
          <div class="ml-3">
            <label class="text-sm font-medium text-gray-700">Recording</label>
            <p class="text-xs text-gray-500">Store call recordings</p>
          </div>
        </div>
        
        <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
          <input
            v-model="artifactConfig.transcript"
            type="checkbox"
            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
          />
          <div class="ml-3">
            <label class="text-sm font-medium text-gray-700">Transcript</label>
            <p class="text-xs text-gray-500">Store conversation transcripts</p>
          </div>
        </div>
        
        <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
          <input
            v-model="artifactConfig.summary"
            type="checkbox"
            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
          />
          <div class="ml-3">
            <label class="text-sm font-medium text-gray-700">Summary</label>
            <p class="text-xs text-gray-500">Store conversation summaries</p>
          </div>
        </div>
        
        <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
          <input
            v-model="artifactConfig.analysis"
            type="checkbox"
            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
          />
          <div class="ml-3">
            <label class="text-sm font-medium text-gray-700">Analysis</label>
            <p class="text-xs text-gray-500">Store analysis results</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue' // Fixed nextTick import

export default {
  name: 'AnalysisConfiguration',
  props: {
    analysisPlan: {
      type: Object,
      default: () => ({})
    },
    artifactPlan: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['update:analysisPlan', 'update:artifactPlan'],
  setup(props, { emit }) {
    console.log('AnalysisConfiguration: Component loaded, nextTick available:', typeof nextTick)
    console.log('AnalysisConfiguration: Props received:', props)
    const isUpdatingFromProps = ref(false)
    const analysisConfig = ref({
      summary: false,
      summaryPrompt: 'You are an expert note-taker. You will be given a transcript of a call. Summarize the call in 2-3 sentences, if applicable.',
      summaryTimeout: 10,
      minMessages: 2,
      successEvaluation: false,
      successEvaluationPrompt: 'You are an expert call evaluator. You will be given a transcript of a call and the system prompt of the AI participant. Determine if the call was successful based on the objectives inferred from the system prompt.',
      successEvaluationTimeout: 10,
      checklist: [
        { checked: false, text: 'Customer inquiry was addressed' },
        { checked: false, text: 'Appropriate information was provided' },
        { checked: false, text: 'Call ended professionally' }
      ],
      structuredData: false,
      structuredDataPrompt: 'You will be given a transcript of a call and the system prompt of the AI participant. Extract structured data from the call based on the following schema...',
      structuredDataTimeout: 10,
      dataSchema: [
        { 
          name: 'field_1', 
          type: 'string', 
          required: false, 
          isEnum: false, 
          description: '',
          showDescription: false
        }
      ],
      sentiment: false,
      topics: false,
      entities: false,
      actionItems: false,
      followUps: false
    })

    const showChecklist = ref(true)

    const artifactConfig = ref({
      recording: false,
      transcript: false,
      summary: false,
      analysis: false
    })

    // Watch for changes and emit updates (but not when updating from props)
    watch(analysisConfig, (newConfig) => {
      // Only emit if this change didn't come from props
      if (!isUpdatingFromProps.value) {
        emit('update:analysisPlan', newConfig)
      }
    }, { deep: true })

    watch(artifactConfig, (newConfig) => {
      // Only emit if this change didn't come from props
      if (!isUpdatingFromProps.value) {
        emit('update:artifactPlan', newConfig)
      }
    }, { deep: true })

    // Checklist methods
    const toggleChecklist = () => {
      showChecklist.value = !showChecklist.value
    }

    const addCriterion = () => {
      analysisConfig.value.checklist.push({ checked: false, text: '' })
    }

    const removeCriterion = (index) => {
      analysisConfig.value.checklist.splice(index, 1)
    }

    // Data Schema methods
    const addProperty = () => {
      analysisConfig.value.dataSchema.push({
        name: `field_${analysisConfig.value.dataSchema.length + 1}`,
        type: 'string',
        required: false,
        isEnum: false,
        description: '',
        showDescription: false
      })
    }

    const removeProperty = (index) => {
      analysisConfig.value.dataSchema.splice(index, 1)
    }

    // Initialize with props
    if (props.analysisPlan && Object.keys(props.analysisPlan).length > 0) {
      isUpdatingFromProps.value = true
      analysisConfig.value = { ...analysisConfig.value, ...props.analysisPlan }
      // Ensure checklist is an array
      if (!Array.isArray(analysisConfig.value.checklist)) {
        analysisConfig.value.checklist = [
          { checked: false, text: 'Customer inquiry was addressed' },
          { checked: false, text: 'Appropriate information was provided' },
          { checked: false, text: 'Call ended professionally' }
        ]
      }
      // Ensure dataSchema is an array
      if (!Array.isArray(analysisConfig.value.dataSchema)) {
        analysisConfig.value.dataSchema = [
          { 
            name: 'field_1', 
            type: 'string', 
            required: false, 
            isEnum: false, 
            description: '',
            showDescription: false
          }
        ]
      }
      // Reset flag after a tick to allow the emit watcher to work again
      setTimeout(() => {
        isUpdatingFromProps.value = false
      }, 0)
    }

    if (props.artifactPlan && Object.keys(props.artifactPlan).length > 0) {
      isUpdatingFromProps.value = true
      artifactConfig.value = { ...artifactConfig.value, ...props.artifactPlan }
      // Reset flag after a tick to allow the emit watcher to work again
      setTimeout(() => {
        isUpdatingFromProps.value = false
      }, 0)
    }

    return {
      analysisConfig,
      artifactConfig,
      showChecklist,
      toggleChecklist,
      addCriterion,
      removeCriterion,
      addProperty,
      removeProperty
    }
  }
}
</script>

<style scoped>
/* Custom checkbox styling */
input[type="checkbox"]:checked {
  background-color: #10b981;
  border-color: #10b981;
}

input[type="checkbox"]:focus {
  outline: 2px solid #10b981;
  outline-offset: 2px;
}

/* Custom slider styling */
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
