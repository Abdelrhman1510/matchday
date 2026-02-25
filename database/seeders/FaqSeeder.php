<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How do I book a seat to watch a match?',
                'answer' => 'To book a seat, browse upcoming matches on the home screen, select the match you want to watch, choose a cafe/branch, pick your preferred seats, and confirm your booking. You will receive a confirmation with a QR code for entry.',
                'category' => 'Booking',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'Can I cancel my booking?',
                'answer' => 'Yes, you can cancel your booking up to a certain number of hours before the match starts (usually 2 hours, depending on the cafe\'s cancellation policy). Go to My Bookings, select the booking, and tap Cancel. Refunds are processed according to the cafe\'s refund policy.',
                'category' => 'Cancellation',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'question' => 'How does the loyalty program work?',
                'answer' => 'Every booking earns you loyalty points. Accumulate points to unlock tiers: Bronze, Silver, Gold, and Platinum. Each tier offers exclusive perks such as priority booking, discounts, and free upgrades. Track your progress in the Loyalty section of the app.',
                'category' => 'Loyalty',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'What payment methods are accepted?',
                'answer' => 'We accept major credit/debit cards (Visa, Mastercard), Apple Pay, mada cards, and STC Pay. You can manage your payment methods in the Payment Methods section of your profile.',
                'category' => 'Payments',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'question' => 'How do I check in at the cafe?',
                'answer' => 'When you arrive at the cafe, show your QR code from the booking pass to the staff. They will scan it to check you in. You can find your QR code in My Bookings > Entry Pass.',
                'category' => 'Booking',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'question' => 'Can I modify my booking after confirmation?',
                'answer' => 'You can modify your booking details such as the number of guests or special requests before the match starts. Seat changes are subject to availability. Go to My Bookings, select the booking, and tap Edit.',
                'category' => 'Booking',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'question' => 'How do refunds work?',
                'answer' => 'Refunds for cancelled bookings are processed within 5-7 business days to your original payment method. The refund amount depends on the cafe\'s cancellation policy and how far in advance you cancel.',
                'category' => 'Payments',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'question' => 'What is the Fan Room feature?',
                'answer' => 'The Fan Room is a live chat room available during matches. Once your booking is confirmed and the match goes live, you can enter the Fan Room to chat with other fans watching the same match at the same venue. Share reactions, comments, and enjoy the game together!',
                'category' => 'General',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }
    }
}
