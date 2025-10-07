<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Profile Settings
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage your account settings and preferences
            </p>
          </div>
        </div>

        <!-- Profile Update Messages -->
        <div v-if="profileMessage.show" class="mt-6">
          <div :class="[
            'rounded-md p-4',
            profileMessage.type === 'success' 
              ? 'bg-green-50 border border-green-200' 
              : 'bg-red-50 border border-red-200'
          ]">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg v-if="profileMessage.type === 'success'" class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-3">
                <p :class="[
                  'text-sm font-medium',
                  profileMessage.type === 'success' ? 'text-green-800' : 'text-red-800'
                ]">
                  {{ profileMessage.text }}
                </p>
              </div>
              <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                  <button @click="profileMessage.show = false" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2" :class="profileMessage.type === 'success' ? 'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-green-600' : 'bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600'">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-8">
          <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <form @submit.prevent="updateProfile">
                <!-- Profile Picture -->
                <div class="mb-6">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                  <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                      <div v-if="user.profile_picture" class="h-20 w-20 rounded-full overflow-hidden">
                        <img :src="user.profile_picture" :alt="user.name" class="h-full w-full object-cover">
                      </div>
                      <div v-else class="h-20 w-20 rounded-full bg-green-600 flex items-center justify-center">
                        <span class="text-white text-xl font-medium">{{ userInitials }}</span>
                      </div>
                    </div>
                    <div class="flex-1">
                      <input
                        type="file"
                        ref="profilePictureInput"
                        @change="handleProfilePictureChange"
                        accept="image/*"
                        class="hidden"
                      />
                      <button
                        type="button"
                        @click="$refs.profilePictureInput.click()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                      >
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Upload Photo
                      </button>
                      <p class="mt-1 text-sm text-gray-500">JPG, PNG or GIF up to 2MB</p>
                    </div>
                  </div>
                </div>

                <!-- Personal Information -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input
                      id="name"
                      v-model="form.name"
                      type="text"
                      required
                      @input="clearProfileErrors"
                      :class="[
                        'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                        profileErrors.name 
                          ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                          : 'border-gray-300 focus:ring-green-500 focus:border-green-500'
                      ]"
                    />
                    <p v-if="profileErrors.name" class="mt-1 text-sm text-red-600">{{ profileErrors.name }}</p>
                  </div>

                  <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input
                      id="email"
                      v-model="form.email"
                      type="email"
                      required
                      disabled
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                    />
                    <p class="mt-1 text-sm text-gray-500">Email cannot be changed</p>
                  </div>

                  <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input
                      id="phone"
                      v-model="form.phone"
                      type="tel"
                      @input="clearProfileErrors"
                      :class="[
                        'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                        profileErrors.phone 
                          ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                          : 'border-gray-300 focus:ring-green-500 focus:border-green-500'
                      ]"
                    />
                    <p v-if="profileErrors.phone" class="mt-1 text-sm text-red-600">{{ profileErrors.phone }}</p>
                  </div>

                  <div>
                    <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                    <input
                      id="company"
                      v-model="form.company"
                      type="text"
                      @input="clearProfileErrors"
                      :class="[
                        'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                        profileErrors.company 
                          ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                          : 'border-gray-300 focus:ring-green-500 focus:border-green-500'
                      ]"
                    />
                    <p v-if="profileErrors.company" class="mt-1 text-sm text-red-600">{{ profileErrors.company }}</p>
                  </div>

                  <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                    <textarea
                      id="bio"
                      v-model="form.bio"
                      rows="3"
                      @input="clearProfileErrors"
                      :class="[
                        'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                        profileErrors.bio 
                          ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                          : 'border-gray-300 focus:ring-green-500 focus:border-green-500'
                      ]"
                      placeholder="Tell us about yourself..."
                    ></textarea>
                    <p v-if="profileErrors.bio" class="mt-1 text-sm text-red-600">{{ profileErrors.bio }}</p>
                  </div>
                </div>

                <!-- Save Button -->
                <div class="mt-6 flex justify-end">
                  <button
                    type="submit"
                    :disabled="loading"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ loading ? 'Saving...' : 'Save Changes' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Password Change Messages -->
          <div v-if="passwordMessage.show" class="mt-6">
            <div :class="[
              'rounded-md p-4',
              passwordMessage.type === 'success' 
                ? 'bg-green-50 border border-green-200' 
                : 'bg-red-50 border border-red-200'
            ]">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg v-if="passwordMessage.type === 'success'" class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <svg v-else class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p :class="[
                    'text-sm font-medium',
                    passwordMessage.type === 'success' ? 'text-green-800' : 'text-red-800'
                  ]">
                    {{ passwordMessage.text }}
                  </p>
                </div>
                <div class="ml-auto pl-3">
                  <div class="-mx-1.5 -my-1.5">
                    <button @click="passwordMessage.show = false" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2" :class="passwordMessage.type === 'success' ? 'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-green-600' : 'bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600'">
                      <span class="sr-only">Dismiss</span>
                      <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Change Password Section -->
          <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
              <form @submit.prevent="changePassword">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input
                      id="current_password"
                      v-model="passwordForm.current_password"
                      type="password"
                      required
                      @input="clearPasswordErrors"
                      :class="[
                        'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                        passwordErrors.current_password 
                          ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                          : 'border-gray-300 focus:ring-green-500 focus:border-green-500'
                      ]"
                    />
                    <p v-if="passwordErrors.current_password" class="mt-1 text-sm text-red-600">{{ passwordErrors.current_password }}</p>
                  </div>

                  <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input
                      id="new_password"
                      v-model="passwordForm.new_password"
                      type="password"
                      required
                      @input="clearPasswordErrors"
                      :class="[
                        'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                        passwordErrors.new_password 
                          ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                          : 'border-gray-300 focus:ring-green-500 focus:border-green-500'
                      ]"
                    />
                    <p v-if="passwordErrors.new_password" class="mt-1 text-sm text-red-600">{{ passwordErrors.new_password }}</p>
                  </div>

                  <div>
                    <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input
                      id="new_password_confirmation"
                      v-model="passwordForm.new_password_confirmation"
                      type="password"
                      required
                      @input="clearPasswordErrors"
                      :class="[
                        'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                        passwordErrors.new_password_confirmation 
                          ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                          : 'border-gray-300 focus:ring-green-500 focus:border-green-500'
                      ]"
                    />
                    <p v-if="passwordErrors.new_password_confirmation" class="mt-1 text-sm text-red-600">{{ passwordErrors.new_password_confirmation }}</p>
                  </div>
                </div>

                <div class="mt-6 flex justify-end">
                  <button
                    type="submit"
                    :disabled="passwordLoading"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    <svg v-if="passwordLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ passwordLoading ? 'Changing...' : 'Change Password' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <SimpleFooter />
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import { updateDocumentTitle } from '../../utils/systemSettings.js'
import axios from 'axios'

export default {
  name: 'Profile',
  components: {
    Navigation,
    SimpleFooter
  },
  data() {
    return {
      user: {},
      form: {
        name: '',
        email: '',
        phone: '',
        company: '',
        bio: ''
      },
      passwordForm: {
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      },
      loading: false,
      passwordLoading: false,
      selectedProfilePicture: null,
      // Success and error messages
      profileMessage: {
        type: '', // 'success' or 'error'
        text: '',
        show: false
      },
      passwordMessage: {
        type: '', // 'success' or 'error'
        text: '',
        show: false
      },
      // Field validation errors
      profileErrors: {},
      passwordErrors: {},
      // Form validation
      profileValid: true,
      passwordValid: true
    }
  },
  computed: {
    userInitials() {
      if (!this.user.name) return '';
      return this.user.name.split(' ').map(n => n[0]).join('').toUpperCase();
    }
  },
  async mounted() {
    await this.loadUser();
    updateDocumentTitle('Profile');
  },
  methods: {
    // Message handling methods
    showProfileMessage(type, text) {
      this.profileMessage = {
        type,
        text,
        show: true
      };
      // Auto-hide after 5 seconds
      setTimeout(() => {
        this.profileMessage.show = false;
      }, 5000);
    },

    showPasswordMessage(type, text) {
      this.passwordMessage = {
        type,
        text,
        show: true
      };
      // Auto-hide after 5 seconds
      setTimeout(() => {
        this.passwordMessage.show = false;
      }, 5000);
    },

    clearMessages() {
      this.profileMessage.show = false;
      this.passwordMessage.show = false;
      this.profileErrors = {};
      this.passwordErrors = {};
    },

    clearProfileErrors() {
      this.profileErrors = {};
    },

    clearPasswordErrors() {
      this.passwordErrors = {};
    },

    // Validation methods
    validateProfileForm() {
      this.profileErrors = {};
      this.profileValid = true;

      if (!this.form.name.trim()) {
        this.profileErrors.name = 'Name is required';
        this.profileValid = false;
      }

      if (this.form.name.length > 255) {
        this.profileErrors.name = 'Name must be less than 255 characters';
        this.profileValid = false;
      }

      if (this.form.phone && this.form.phone.length > 20) {
        this.profileErrors.phone = 'Phone number must be less than 20 characters';
        this.profileValid = false;
      }

      if (this.form.company && this.form.company.length > 255) {
        this.profileErrors.company = 'Company name must be less than 255 characters';
        this.profileValid = false;
      }

      if (this.form.bio && this.form.bio.length > 1000) {
        this.profileErrors.bio = 'Bio must be less than 1000 characters';
        this.profileValid = false;
      }

      return this.profileValid;
    },

    validatePasswordForm() {
      this.passwordErrors = {};
      this.passwordValid = true;

      if (!this.passwordForm.current_password) {
        this.passwordErrors.current_password = 'Current password is required';
        this.passwordValid = false;
      }

      if (!this.passwordForm.new_password) {
        this.passwordErrors.new_password = 'New password is required';
        this.passwordValid = false;
      } else if (this.passwordForm.new_password.length < 8) {
        this.passwordErrors.new_password = 'New password must be at least 8 characters';
        this.passwordValid = false;
      }

      if (!this.passwordForm.new_password_confirmation) {
        this.passwordErrors.new_password_confirmation = 'Password confirmation is required';
        this.passwordValid = false;
      } else if (this.passwordForm.new_password !== this.passwordForm.new_password_confirmation) {
        this.passwordErrors.new_password_confirmation = 'Passwords do not match';
        this.passwordValid = false;
      }

      return this.passwordValid;
    },

    async loadUser() {
      try {
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        this.user = user;
        this.form = {
          name: user.name || '',
          email: user.email || '',
          phone: user.phone || '',
          company: user.company || '',
          bio: user.bio || ''
        };
      } catch (error) {
        console.error('Error loading user:', error);
        this.showProfileMessage('error', 'Failed to load user profile');
      }
    },
    
    async updateProfile() {
      // Clear previous messages
      this.clearMessages();
      
      // Validate form
      if (!this.validateProfileForm()) {
        this.showProfileMessage('error', 'Please fix the errors in the form');
        return;
      }

      this.loading = true;
      try {
        const formData = new FormData();
        formData.append('name', this.form.name);
        formData.append('phone', this.form.phone || '');
        formData.append('company', this.form.company || '');
        formData.append('bio', this.form.bio || '');
        
        if (this.selectedProfilePicture) {
          formData.append('profile_picture', this.selectedProfilePicture);
        }

        const response = await axios.post('/api/user', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          }
        });

        if (response.data.success) {
          // Update user in localStorage
          const user = JSON.parse(localStorage.getItem('user') || '{}');
          const updatedUser = { ...user, ...response.data.data };
          localStorage.setItem('user', JSON.stringify(updatedUser));
          
          this.user = updatedUser;
          this.selectedProfilePicture = null; // Reset file input
          
          // Show success message
          this.showProfileMessage('success', 'Profile updated successfully!');
          this.$toast.success('Profile updated successfully');
        } else {
          const errorMessage = response.data.message || 'Failed to update profile';
          this.showProfileMessage('error', errorMessage);
          this.$toast.error(errorMessage);
        }
      } catch (error) {
        console.error('Error updating profile:', error);
        let errorMessage = 'An error occurred while updating profile';
        
        if (error.response && error.response.data) {
          if (error.response.data.errors) {
            // Handle validation errors from backend
            const errors = error.response.data.errors;
            Object.keys(errors).forEach(field => {
              this.profileErrors[field] = errors[field][0];
            });
            errorMessage = 'Please fix the errors in the form';
          } else {
            errorMessage = error.response.data.message || errorMessage;
          }
        }
        
        this.showProfileMessage('error', errorMessage);
        this.$toast.error(errorMessage);
      } finally {
        this.loading = false;
      }
    },
    
    handleProfilePictureChange(event) {
      const file = event.target.files[0];
      if (file) {
        this.selectedProfilePicture = file;
      }
    },
    
    async changePassword() {
      // Clear previous messages
      this.clearMessages();
      
      // Validate form
      if (!this.validatePasswordForm()) {
        this.showPasswordMessage('error', 'Please fix the errors in the form');
        return;
      }

      this.passwordLoading = true;
      try {
        const response = await axios.put('/api/user/password', this.passwordForm);

        if (response.data.success) {
          // Show success message
          this.showPasswordMessage('success', 'Password changed successfully!');
          this.$toast.success('Password changed successfully');
          
          // Reset form
          this.passwordForm = {
            current_password: '',
            new_password: '',
            new_password_confirmation: ''
          };
        } else {
          const errorMessage = response.data.message || 'Failed to change password';
          this.showPasswordMessage('error', errorMessage);
          this.$toast.error(errorMessage);
        }
      } catch (error) {
        console.error('Error changing password:', error);
        let errorMessage = 'An error occurred while changing password';
        
        if (error.response && error.response.data) {
          if (error.response.data.errors) {
            // Handle validation errors from backend
            const errors = error.response.data.errors;
            Object.keys(errors).forEach(field => {
              this.passwordErrors[field] = errors[field][0];
            });
            errorMessage = 'Please fix the errors in the form';
          } else {
            errorMessage = error.response.data.message || errorMessage;
          }
        }
        
        this.showPasswordMessage('error', errorMessage);
        this.$toast.error(errorMessage);
      } finally {
        this.passwordLoading = false;
      }
    }
  }
}
</script> 