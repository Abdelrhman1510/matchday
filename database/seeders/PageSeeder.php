<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'content' => '<h1>Privacy Policy</h1>
<p>Last updated: February 2026</p>

<h2>1. Introduction</h2>
<p>Welcome to MatchDay ("we", "our", or "us"). We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our mobile application.</p>

<h2>2. Information We Collect</h2>
<p>We collect information that you provide directly to us, including:</p>
<ul>
<li>Name, email address, and phone number</li>
<li>Payment information (processed securely through our payment providers)</li>
<li>Location data (with your permission) to show nearby cafes</li>
<li>Booking history and preferences</li>
<li>Device information and usage data</li>
</ul>

<h2>3. How We Use Your Information</h2>
<p>We use the information we collect to:</p>
<ul>
<li>Process and manage your bookings</li>
<li>Provide customer support</li>
<li>Send notifications about your bookings and matches</li>
<li>Improve our services and user experience</li>
<li>Comply with legal obligations</li>
</ul>

<h2>4. Data Security</h2>
<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<h2>5. Contact Us</h2>
<p>If you have questions about this Privacy Policy, please contact us at support@matchday.app</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'content' => '<h1>Terms & Conditions</h1>
<p>Last updated: February 2026</p>

<h2>1. Acceptance of Terms</h2>
<p>By accessing and using the MatchDay application, you accept and agree to be bound by these Terms and Conditions. If you do not agree to these terms, please do not use the application.</p>

<h2>2. User Accounts</h2>
<p>To use certain features of the application, you must register for an account. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>

<h2>3. Bookings</h2>
<p>All bookings are subject to availability. Once a booking is confirmed, you will receive a confirmation notification and a QR code for entry. Cancellation policies vary by cafe and are displayed at the time of booking.</p>

<h2>4. Payments</h2>
<p>All payments are processed securely through our approved payment providers. Prices are displayed in Saudi Riyals (SAR) unless otherwise specified. Refunds are subject to the cafe\'s cancellation policy.</p>

<h2>5. User Conduct</h2>
<p>You agree to use the application in accordance with all applicable laws and regulations. You shall not use the application for any unlawful or prohibited purpose.</p>

<h2>6. Limitation of Liability</h2>
<p>MatchDay shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the application.</p>

<h2>7. Contact</h2>
<p>For questions regarding these terms, contact us at support@matchday.app</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'data-usage',
                'title' => 'Data Usage Policy',
                'content' => '<h1>Data Usage Policy</h1>
<p>Last updated: February 2026</p>

<h2>1. Overview</h2>
<p>This Data Usage Policy explains how MatchDay collects, processes, and stores your data. We are committed to transparency and giving you control over your information.</p>

<h2>2. Data Collection</h2>
<p>We collect the following types of data:</p>
<ul>
<li><strong>Account Data:</strong> Name, email, phone number, and profile information</li>
<li><strong>Usage Data:</strong> App interactions, booking history, and feature usage</li>
<li><strong>Device Data:</strong> Device type, operating system, and app version</li>
<li><strong>Location Data:</strong> With your consent, approximate location for nearby cafe suggestions</li>
</ul>

<h2>3. Data Storage</h2>
<p>Your data is stored securely on servers located in compliant data centers. We retain your data only as long as necessary to provide our services and fulfill legal obligations.</p>

<h2>4. Data Sharing</h2>
<p>We do not sell your personal data. We may share data with:</p>
<ul>
<li>Cafe partners (only booking-related information)</li>
<li>Payment processors (for transaction processing)</li>
<li>Service providers who assist in operating our platform</li>
</ul>

<h2>5. Your Rights</h2>
<p>You have the right to access, correct, delete, or export your personal data. Contact us at support@matchday.app to exercise these rights.</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'cookie-policy',
                'title' => 'Cookie Policy',
                'content' => '<h1>Cookie Policy</h1>
<p>Last updated: February 2026</p>

<h2>1. What Are Cookies</h2>
<p>Cookies are small text files stored on your device when you use our services. While our mobile application primarily uses local storage and tokens, our web services may use cookies.</p>

<h2>2. How We Use Cookies</h2>
<p>We use cookies and similar technologies for:</p>
<ul>
<li><strong>Authentication:</strong> To keep you signed in and verify your identity</li>
<li><strong>Preferences:</strong> To remember your settings and preferences</li>
<li><strong>Analytics:</strong> To understand how our services are used and improve them</li>
<li><strong>Security:</strong> To detect and prevent fraudulent activity</li>
</ul>

<h2>3. Managing Cookies</h2>
<p>You can control cookies through your browser settings. Note that disabling certain cookies may affect the functionality of our services.</p>

<h2>4. Third-Party Cookies</h2>
<p>Some of our service providers may place cookies on your device. These are governed by the respective provider\'s privacy policies.</p>

<h2>5. Updates</h2>
<p>We may update this Cookie Policy from time to time. Changes will be posted on this page with an updated revision date.</p>

<h2>6. Contact</h2>
<p>For questions about our use of cookies, contact us at support@matchday.app</p>',
                'is_active' => true,
            ],
        ];

            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'content' => '<h1>Terms &amp; Conditions</h1><p>Welcome to <strong>MatchDay</strong>. By using our application, you accept and agree to be bound by these Terms and Conditions.</p><h2>Bookings</h2><p>All bookings are subject to availability. Payments must be completed to confirm a booking. Cancellation policies are set by individual cafes and are displayed at the time of booking.</p><h2>Payments</h2><p>All payments are processed securely. Refunds are subject to the cafe\'s cancellation policy.</p><h2>User Conduct</h2><p>You agree to use the application lawfully and respectfully. Fake accounts, booking manipulation, and QR code sharing are strictly prohibited.</p><h2>Limitation of Liability</h2><p>MatchDay is not responsible for issues arising from cafe services on the platform.</p><h2>Contact</h2><p>For questions, contact us at support@matchday.app</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'faq',
                'title' => 'Frequently Asked Questions',
                'content' => '<h1>Frequently Asked Questions</h1><h2>What is MatchDay?</h2><p>MatchDay is a platform that lets you book seats at cafes to watch live sports matches in a great atmosphere.</p><h2>How do I book a seat?</h2><p>Browse upcoming matches, select your seats, complete payment, and receive your booking confirmation with a QR code.</p><h2>How do I check in?</h2><p>Present your QR code at the cafe entrance. Staff will scan it to check you in. Each QR code is valid for one check-in only.</p><h2>Can I cancel my booking?</h2><p>Yes, cancellations are subject to the cafe\'s cancellation policy shown before booking.</p><h2>Is my payment secure?</h2><p>Yes, all payments are processed through encrypted, secure payment gateways.</p><h2>How do I contact support?</h2><p>Use the Support section in the MatchDay app to submit a ticket or report an issue.</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'usage-policy',
                'title' => 'Usage Policy',
                'content' => '<h1>Usage Policy</h1><p>By using <strong>MatchDay</strong>, you agree to the following terms of acceptable use.</p><h2>Acceptable Use</h2><p>You agree to provide accurate information, use the app lawfully, and respect cafe staff and other attendees.</p><h2>Prohibited Activities</h2><ul><li>Creating fake accounts or bookings</li><li>Reselling bookings purchased through MatchDay</li><li>Using automated tools to make bookings</li><li>Harassing other users or cafe staff</li></ul><h2>QR Code Policy</h2><p>Each QR code is valid for a single check-in only. Sharing, duplicating, or transferring QR codes is strictly prohibited and may result in immediate account suspension.</p><h2>Account Suspension</h2><p>MatchDay reserves the right to suspend or terminate accounts that violate this policy without prior notice.</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'cookies-policy',
                'title' => 'Cookies Policy',
                'content' => '<h1>Cookies Policy</h1><p><strong>MatchDay</strong> uses cookies and similar technologies to improve your experience.</p><h2>Types of Cookies We Use</h2><ul><li><strong>Essential Cookies:</strong> Required for authentication and session management</li><li><strong>Analytics Cookies:</strong> Help us understand how users interact with the app</li><li><strong>Preference Cookies:</strong> Remember your settings and preferences</li></ul><h2>Managing Cookies</h2><p>You can control cookies through your device or browser settings. Note that disabling essential cookies may affect app functionality.</p><h2>Third-Party Cookies</h2><p>Some features may use third-party cookies governed by the respective provider\'s privacy policies.</p><h2>Updates</h2><p>Continued use of MatchDay after changes to this policy constitutes acceptance of the updated terms.</p>',
                'is_active' => true,
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
