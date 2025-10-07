#!/bin/bash

echo "🔧 Fixing Stripe Production Environment Configuration"
echo ""

# Check current configuration
echo "📋 Current Stripe Configuration:"
grep -E "STRIPE_|MIX_STRIPE_" .env
echo ""

echo "⚠️  ISSUE DETECTED: You're using test keys but trying to process real payments!"
echo ""

echo "🔍 Analysis:"
echo "- Current keys start with 'pk_test_' and 'sk_test_' (TEST ENVIRONMENT)"
echo "- You're trying to process real payments (PRODUCTION ENVIRONMENT)"
echo "- Test payment methods are invalid in production environment"
echo ""

echo "📋 Solutions:"
echo ""
echo "1️⃣  SWITCH TO PRODUCTION KEYS (Recommended for live payments):"
echo "   - Log into Stripe Dashboard"
echo "   - Switch to 'Live mode' (toggle in top-right)"
echo "   - Go to Developers → API keys"
echo "   - Copy your production keys (start with 'pk_live_' and 'sk_live_')"
echo "   - Update your .env file with production keys"
echo ""

echo "2️⃣  TEST WITH TEST CARDS (For development):"
echo "   - Use test card: 4242 4242 4242 4242"
echo "   - Any future expiry date"
echo "   - Any 3-digit CVC"
echo ""

echo "3️⃣  UPDATE ENVIRONMENT VARIABLES:"
echo "   - Set STRIPE_TEST_MODE=false for production"
echo "   - Update webhook endpoint to production URL"
echo ""

echo "🚨 IMPORTANT:"
echo "- Production environment processes REAL MONEY"
echo "- Test thoroughly before going live"
echo "- Never use test keys for real payments"
echo ""

echo "📝 Next Steps:"
echo "1. Choose your approach (production keys or test cards)"
echo "2. Update your .env file accordingly"
echo "3. Test the payment flow"
echo "4. Monitor for any errors"
echo ""

# Create a backup
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Backup created: .env.backup.$(date +%Y%m%d_%H%M%S)" 