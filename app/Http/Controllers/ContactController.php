<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\ResellerMailManager;
use App\Services\ResellerEmailService;

class ContactController extends Controller
{
    /**
     * Store a newly created contact message.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get reseller from domain context
            $reseller = app('currentReseller');
            
            $contact = Contact::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'subject' => $request->subject,
                'message' => $request->message,
                'status' => 'new',
                'reseller_id' => $reseller ? $reseller->id : null,
                'user_id' => null // Public contact form, no user associated
            ]);

            // Send email notification to admin
            try {
                // Set reseller mail configuration
                if ($reseller) {
                    ResellerMailManager::setMailConfig($reseller);
                }
                
                // Get reseller branding data
                $branding = $reseller ? ResellerEmailService::getResellerBranding($reseller) : [
                    'app_name' => 'AI Phone System',
                    'support_email' => 'sulus.ai@gmail.com',
                    'company_name' => 'AI Phone System'
                ];
                
                // Get contact statistics
                $totalContacts = Contact::count();
                $newToday = Contact::whereDate('created_at', today())->count();
                
                Mail::send('emails.contact-submission', [
                    'contact' => $contact,
                    'totalContacts' => $totalContacts,
                    'newToday' => $newToday,
                    'headerTitle' => 'New Contact Form Submission',
                    'headerSubtitle' => $branding['company_name'] . ' Website',
                    'branding' => $branding,
                ], function($message) use ($branding) {
                    $message->to($branding['support_email'])
                            ->subject("New Contact Form Submission - {$branding['app_name']}");
                });
            } catch (\Exception $e) {
                // Log email error but don't fail the contact submission
                Log::error('Failed to send contact form email to admin: ' . $e->getMessage());
            }

            // Send confirmation email to customer
            try {
                // Set reseller mail configuration (already set above, but ensure it's set)
                if ($reseller) {
                    ResellerMailManager::setMailConfig($reseller);
                }
                
                // Get reseller branding data (already retrieved above)
                $branding = $reseller ? ResellerEmailService::getResellerBranding($reseller) : [
                    'app_name' => 'AI Phone System',
                    'support_email' => 'sulus.ai@gmail.com',
                    'company_name' => 'AI Phone System'
                ];
                
                Mail::send('emails.contact-confirmation', [
                    'contact' => $contact,
                    'headerTitle' => 'Thank You for Contacting Us',
                    'headerSubtitle' => $branding['company_name'] . ' Support',
                    'branding' => $branding,
                ], function($message) use ($contact, $branding) {
                    $message->to($contact->email)
                            ->subject("Thank You for Contacting {$branding['app_name']} - Reference #" . $contact->id);
                });
            } catch (\Exception $e) {
                // Log email error but don't fail the contact submission
                Log::error('Failed to send contact confirmation email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message! We will get back to you soon.',
                'data' => $contact
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'There was an error sending your message. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all contact messages (for admin use).
     */
    public function index(Request $request)
    {
        $query = Contact::contentProtection();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by subject
        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $contacts = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }

    /**
     * Get a specific contact message.
     */
    public function show($id)
    {
        $contact = Contact::contentProtection()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $contact
        ]);
    }

    /**
     * Update contact status.
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,read,replied,closed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status',
                'errors' => $validator->errors()
            ], 422);
        }

        $contact = Contact::contentProtection()->findOrFail($id);
        $contact->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Contact status updated successfully',
            'data' => $contact
        ]);
    }
}
