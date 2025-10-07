<template>
  <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <router-link to="/" class="inline-flex items-center text-primary-600 hover:text-primary-500 mb-4">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Back to Home
        </router-link>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Terms of Service</h1>
        <p class="text-lg text-gray-600">Last updated: {{ new Date().toLocaleDateString() }}</p>
      </div>

      <!-- Content -->
      <div class="bg-white shadow-lg rounded-lg p-8">
        <div class="prose prose-lg max-w-none">
          <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Acceptance of Terms</h2>
          <p class="mb-6">
            By accessing and using {{ companyName }} ("the Service"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Description of Service</h2>
          <p class="mb-6">
            {{ companyName }} provides voice AI assistant services, including but not limited to:
          </p>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li>Voice assistant creation and customization</li>
            <li>AI-powered conversation management</li>
            <li>Integration with various platforms</li>
            <li>Call handling and routing services</li>
            <li>Analytics and reporting features</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. User Accounts</h2>
          <p class="mb-6">
            To access certain features of the Service, you must register for an account. You agree to:
          </p>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li>Provide accurate, current, and complete information</li>
            <li>Maintain and update your account information</li>
            <li>Keep your password secure and confidential</li>
            <li>Accept responsibility for all activities under your account</li>
            <li>Notify us immediately of any unauthorized use</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Acceptable Use</h2>
          <p class="mb-6">
            You agree not to use the Service to:
          </p>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li>Violate any applicable laws or regulations</li>
            <li>Infringe upon the rights of others</li>
            <li>Transmit harmful, offensive, or inappropriate content</li>
            <li>Attempt to gain unauthorized access to our systems</li>
            <li>Interfere with the proper functioning of the Service</li>
            <li>Use the Service for spam or unsolicited communications</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Subscription and Payment</h2>
          <p class="mb-6">
            The Service is offered on a subscription basis. You agree to:
          </p>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li>Pay all fees associated with your subscription</li>
            <li>Provide accurate billing information</li>
            <li>Authorize recurring payments as applicable</li>
            <li>Understand that fees are non-refundable except as required by law</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Privacy and Data Protection</h2>
          <p class="mb-6">
            Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the Service, to understand our practices.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Intellectual Property</h2>
          <p class="mb-6">
            The Service and its original content, features, and functionality are owned by {{ companyName }} and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Limitation of Liability</h2>
          <p class="mb-6">
            In no event shall {{ companyName }}, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your use of the Service.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. Termination</h2>
          <p class="mb-6">
            We may terminate or suspend your account and bar access to the Service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever and without limitation, including but not limited to a breach of the Terms.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. Changes to Terms</h2>
          <p class="mb-6">
            We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days notice prior to any new terms taking effect.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. Contact Information</h2>
          <p class="mb-6">
            If you have any questions about these Terms of Service, please contact us at:
          </p>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="mb-2"><strong>Email:</strong> {{ contactEmail }}</p>
            <p><strong>Phone:</strong> {{ contactPhone }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { updateDocumentTitle, getSystemSettings } from '../../utils/systemSettings.js'

export default {
  name: 'TermsOfService',
  setup() {
    const contactEmail = ref('support@sulus.ai')
    const contactPhone = ref('(682) 582 8396')
    const companyName = ref('sulus.ai')

    const loadContactInfo = async () => {
      try {
        const settings = await getSystemSettings()
        contactEmail.value = settings.company_email || 'support@sulus.ai'
        contactPhone.value = settings.company_phone || '(682) 582 8396'
        companyName.value = settings.company_name || 'sulus.ai'
      } catch (error) {
        console.error('Error loading contact info:', error)
      }
    }

    onMounted(async () => {
      updateDocumentTitle('Terms of Service')
      await loadContactInfo()
    })

    return {
      contactEmail,
      contactPhone,
      companyName
    }
  }
}
</script>

<style scoped>
.prose h2 {
  margin-top: 2rem;
  margin-bottom: 1rem;
}

.prose ul {
  margin-bottom: 1.5rem;
}

.prose p {
  margin-bottom: 1rem;
}
</style> 