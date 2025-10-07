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
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Privacy Policy</h1>
        <p class="text-lg text-gray-600">Last updated: {{ new Date().toLocaleDateString() }}</p>
      </div>

      <!-- Content -->
      <div class="bg-white shadow-lg rounded-lg p-8">
        <div class="prose prose-lg max-w-none">
          <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Introduction</h2>
          <p class="mb-6">
            Welcome to {{ companyName }}. We respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our voice AI platform and services.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Information We Collect</h2>
          <p class="mb-6">
            We collect information you provide directly to us, such as when you create an account, use our services, or contact us for support.
          </p>
          
          <h3 class="text-xl font-semibold text-gray-900 mb-3">Personal Information</h3>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li>Name and contact information (email, phone number)</li>
            <li>Account credentials and profile information</li>
            <li>Payment and billing information</li>
            <li>Communication preferences</li>
          </ul>

          <h3 class="text-xl font-semibold text-gray-900 mb-3">Usage Information</h3>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li>Voice call recordings and transcripts</li>
            <li>Assistant configurations and settings</li>
            <li>Service usage patterns and analytics</li>
            <li>Device and browser information</li>
            <li>IP addresses and location data</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. How We Use Your Information</h2>
          <p class="mb-6">
            We use the information we collect to:
          </p>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li>Provide, maintain, and improve our services</li>
            <li>Process transactions and send related information</li>
            <li>Send technical notices, updates, and support messages</li>
            <li>Respond to your comments and questions</li>
            <li>Develop new products and services</li>
            <li>Ensure security and prevent fraud</li>
            <li>Comply with legal obligations</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. Information Sharing</h2>
          <p class="mb-6">
            We do not sell, trade, or otherwise transfer your personal information to third parties except in the following circumstances:
          </p>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li><strong>Service Providers:</strong> We may share information with trusted third-party service providers who assist us in operating our platform</li>
            <li><strong>Legal Requirements:</strong> We may disclose information when required by law or to protect our rights and safety</li>
            <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred</li>
            <li><strong>Consent:</strong> We may share information with your explicit consent</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Data Security</h2>
          <p class="mb-6">
            We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. These measures include:
          </p>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li>Encryption of data in transit and at rest</li>
            <li>Regular security assessments and updates</li>
            <li>Access controls and authentication measures</li>
            <li>Employee training on data protection</li>
            <li>Incident response procedures</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Data Retention</h2>
          <p class="mb-6">
            We retain your personal information for as long as necessary to provide our services and fulfill the purposes outlined in this policy. We may retain certain information for longer periods when required by law or for legitimate business purposes.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Your Rights</h2>
          <p class="mb-6">
            Depending on your location, you may have the following rights regarding your personal information:
          </p>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li><strong>Access:</strong> Request access to your personal information</li>
            <li><strong>Correction:</strong> Request correction of inaccurate information</li>
            <li><strong>Deletion:</strong> Request deletion of your personal information</li>
            <li><strong>Portability:</strong> Request a copy of your data in a portable format</li>
            <li><strong>Objection:</strong> Object to certain processing activities</li>
            <li><strong>Restriction:</strong> Request restriction of processing</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Cookies and Tracking</h2>
          <p class="mb-6">
            We use cookies and similar tracking technologies to enhance your experience on our platform. These technologies help us:
          </p>
          <ul class="list-disc pl-6 mb-6 space-y-2">
            <li>Remember your preferences and settings</li>
            <li>Analyze site traffic and usage patterns</li>
            <li>Provide personalized content and features</li>
            <li>Improve our services and user experience</li>
          </ul>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Third-Party Services</h2>
          <p class="mb-6">
            Our platform may integrate with third-party services and APIs. These services have their own privacy policies, and we encourage you to review them. We are not responsible for the privacy practices of third-party services.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. Children's Privacy</h2>
          <p class="mb-6">
            Our services are not intended for children under the age of 13. We do not knowingly collect personal information from children under 13. If you believe we have collected information from a child under 13, please contact us immediately.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. International Transfers</h2>
          <p class="mb-6">
            Your information may be transferred to and processed in countries other than your own. We ensure that such transfers comply with applicable data protection laws and implement appropriate safeguards to protect your information.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. Changes to This Policy</h2>
          <p class="mb-6">
            We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new policy on our website and updating the "Last updated" date. Your continued use of our services after such changes constitutes acceptance of the updated policy.
          </p>

          <h2 class="text-2xl font-semibold text-gray-900 mb-4">12. Contact Us</h2>
          <p class="mb-6">
            If you have any questions about this Privacy Policy or our data practices, please contact us:
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
  name: 'PrivacyPolicy',
  setup() {
    const contactEmail = ref('privacy@sulus.ai')
    const contactPhone = ref('(682) 582 8396')
    const companyName = ref('sulus.ai')

    const loadContactInfo = async () => {
      try {
        const settings = await getSystemSettings()
        contactEmail.value = settings.company_email || 'privacy@sulus.ai'
        contactPhone.value = settings.company_phone || '(682) 582 8396'
        companyName.value = settings.company_name || 'sulus.ai'
      } catch (error) {
        console.error('Error loading contact info:', error)
      }
    }

    onMounted(async () => {
      updateDocumentTitle('Privacy Policy')
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

.prose h3 {
  margin-top: 1.5rem;
  margin-bottom: 0.5rem;
}

.prose ul {
  margin-bottom: 1.5rem;
}

.prose p {
  margin-bottom: 1rem;
}
</style> 