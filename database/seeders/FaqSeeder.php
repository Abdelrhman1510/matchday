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
                'question_ar' => 'كيف يمكنني حجز مقعد لمشاهدة مباراة؟',
                'answer' => 'To book a seat, browse upcoming matches on the home screen, select the match you want to watch, choose a cafe/branch, pick your preferred seats, and confirm your booking. You will receive a confirmation with a QR code for entry.',
                'answer_ar' => 'لحجز مقعد، تصفح المباريات القادمة على الشاشة الرئيسية، اختر المباراة التي ترغب في مشاهدتها، اختر المقهى/الفرع، حدد مقاعدك المفضلة، ثم أكد حجزك. ستتلقى تأكيدًا مع رمز استجابة سريعة (QR code) للدخول.',
                'category' => 'Booking',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'Can I cancel my booking?',
                'question_ar' => 'هل يمكنني إلغاء حجزي؟',
                'answer' => 'Yes, you can cancel your booking up to a certain number of hours before the match starts (usually 2 hours, depending on the cafe\'s cancellation policy). Go to My Bookings, select the booking, and tap Cancel. Refunds are processed according to the cafe\'s refund policy.',
                'answer_ar' => 'نعم، يمكنك إلغاء حجزك قبل عدد معين من الساعات من بدء المباراة (عادة ساعتان، حسب سياسة الإلغاء الخاصة بالمقهى). اذهب إلى حجوزاتي، اختر الحجز، واضغط على إلغاء. تتم معالجة المبالغ المستردة وفقاً لسياسة الاسترجاع الخاصة بالمقهى.',
                'category' => 'Cancellation',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'question' => 'How does the loyalty program work?',
                'question_ar' => 'كيف يعمل برنامج الولاء؟',
                'answer' => 'Every booking earns you loyalty points. Accumulate points to unlock tiers: Bronze, Silver, Gold, and Platinum. Each tier offers exclusive perks such as priority booking, discounts, and free upgrades. Track your progress in the Loyalty section of the app.',
                'answer_ar' => 'كل حجز يمنحك نقاط ولاء. اجمع النقاط لفتح مستويات: البرونزي، الفضي، الذهبي، والبلاتيني. يقدم كل مستوى مزايا حصرية مثل أولوية الحجز، والخصومات، والترقيات المجانية. تتبع تقدمك في قسم الولاء في التطبيق.',
                'category' => 'Loyalty',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'What payment methods are accepted?',
                'question_ar' => 'ما هي طرق الدفع المقبولة؟',
                'answer' => 'We accept major credit/debit cards (Visa, Mastercard), Apple Pay, mada cards, and STC Pay. You can manage your payment methods in the Payment Methods section of your profile.',
                'answer_ar' => 'نحن نقبل بطاقات الائتمان/الخصم الرئيسية (فيزا، ماستركارد)، Apple Pay، بطاقات مدى، وSTC Pay. يمكنك إدارة طرق الدفع الخاصة بك في قسم طرق الدفع في ملفك الشخصي.',
                'category' => 'Payments',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'question' => 'How do I check in at the cafe?',
                'question_ar' => 'كيف أقوم بتسجيل الدخول في المقهى؟',
                'answer' => 'When you arrive at the cafe, show your QR code from the booking pass to the staff. They will scan it to check you in. You can find your QR code in My Bookings > Entry Pass.',
                'answer_ar' => 'عند وصولك إلى المقهى، أظهر رمز الاستجابة السريعة (QR code) الخاص بتذكرة الحجز للموظفين. سيقومون بمسحه لتسجيل دخولك. يمكنك العثور على الرمز في حجوزاتي > تذكرة الدخول.',
                'category' => 'Booking',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'question' => 'Can I modify my booking after confirmation?',
                'question_ar' => 'هل يمكنني تعديل حجزي بعد التأكيد؟',
                'answer' => 'You can modify your booking details such as the number of guests or special requests before the match starts. Seat changes are subject to availability. Go to My Bookings, select the booking, and tap Edit.',
                'answer_ar' => 'يمكنك تعديل تفاصيل حجزك مثل عدد الضيوف أو الطلبات الخاصة قبل بدء المباراة. تغيير المقاعد يخضع للتوافر. اذهب إلى حجوزاتي، اختر الحجز، واضغط على تعديل.',
                'category' => 'Booking',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'question' => 'How do refunds work?',
                'question_ar' => 'كيف تعمل المبالغ المستردة؟',
                'answer' => 'Refunds for cancelled bookings are processed within 5-7 business days to your original payment method. The refund amount depends on the cafe\'s cancellation policy and how far in advance you cancel.',
                'answer_ar' => 'تتم معالجة المبالغ المستردة للحجوزات الملغاة خلال 5-7 أيام عمل إلى طريقة الدفع الأصلية. يعتمد مبلغ الاسترداد على سياسة الإلغاء الخاصة بالمقهى ومدى وقت الإلغاء قبل المباراة.',
                'category' => 'Payments',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'question' => 'What is the Fan Room feature?',
                'question_ar' => 'ما هي ميزة غرفة المشجعين (Fan Room)؟',
                'answer' => 'The Fan Room is a live chat room available during matches. Once your booking is confirmed and the match goes live, you can enter the Fan Room to chat with other fans watching the same match at the same venue. Share reactions, comments, and enjoy the game together!',
                'answer_ar' => 'غرفة المشجعين هي غرفة دردشة حية متاحة أثناء المباريات. بمجرد تأكيد حجزك وبدء المباراة، يمكنك الدخول إلى غرفة المشجعين للدردشة مع المشجعين الآخرين الذين يشاهدون نفس المباراة في نفس المكان. شارك تفاعلاتك، تعليقاتك، واستمتعوا بالمباراة معًا!',
                'category' => 'General',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }
    }
}
