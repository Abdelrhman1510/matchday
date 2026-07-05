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
                'title_ar' => 'سياسة الخصوصية',
                'content_ar' => '<h1>سياسة الخصوصية</h1><p>آخر تحديث: فبراير ٢٠٢٦</p><h2>١. مقدمة</h2><p>مرحبًا بك في <strong>TAB3</strong>. نحن ملتزمون بحماية معلوماتك الشخصية وحقك في الخصوصية.</p><h2>٢. المعلومات التي نجمعها</h2><ul><li>الاسم والبريد الإلكتروني ورقم الهاتف</li><li>معلومات الدفع (تتم معالجتها بأمان عبر مزوّدي الدفع لدينا)</li><li>بيانات الموقع (بإذنك) لعرض المقاهي القريبة</li><li>سجل الحجوزات والتفضيلات</li><li>معلومات الجهاز وبيانات الاستخدام</li></ul><h2>٣. كيف نستخدم معلوماتك</h2><ul><li>معالجة وإدارة حجوزاتك</li><li>إرسال إشعارات حول حجوزاتك والمباريات</li><li>تحسين خدماتنا وتجربة المستخدم</li><li>الامتثال للالتزامات القانونية</li></ul><h2>٤. أمان البيانات</h2><p>نطبّق تدابير تقنية وتنظيمية مناسبة لحماية معلوماتك الشخصية.</p><h2>٥. اتصل بنا</h2><p>إذا كان لديك أي أسئلة حول سياسة الخصوصية هذه، يرجى التواصل معنا على support@tab3.app</p>',
                'content' => '<h1>Privacy Policy</h1><p>Last updated: February 2026</p><h2>1. Introduction</h2><p>Welcome to <strong>TAB3</strong>. We are committed to protecting your personal information and your right to privacy.</p><h2>2. Information We Collect</h2><ul><li>Name, email address, and phone number</li><li>Payment information (processed securely through our payment providers)</li><li>Location data (with your permission) to show nearby cafes</li><li>Booking history and preferences</li><li>Device information and usage data</li></ul><h2>3. How We Use Your Information</h2><ul><li>Process and manage your bookings</li><li>Send notifications about your bookings and matches</li><li>Improve our services and user experience</li><li>Comply with legal obligations</li></ul><h2>4. Data Security</h2><p>We implement appropriate technical and organizational measures to protect your personal information.</p><h2>5. Contact Us</h2><p>If you have questions about this Privacy Policy, please contact us at support@tab3.app</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'title_ar' => 'الشروط والأحكام',
                'content_ar' => '<h1>الشروط والأحكام</h1><p>آخر تحديث: فبراير ٢٠٢٦</p><h2>١. قبول الشروط</h2><p>بوصولك إلى <strong>TAB3</strong> واستخدامه، فإنك تقبل الالتزام بهذه الشروط والأحكام.</p><h2>٢. حسابات المستخدمين</h2><p>أنت مسؤول عن الحفاظ على سرية بيانات اعتماد حسابك.</p><h2>٣. الحجوزات</h2><p>تخضع جميع الحجوزات للتوفّر. بمجرد تأكيد الحجز ستتلقى رمز QR للدخول. تختلف سياسات الإلغاء حسب المقهى.</p><h2>٤. المدفوعات</h2><p>تتم معالجة جميع المدفوعات بأمان. تخضع المبالغ المستردة لسياسة الإلغاء الخاصة بالمقهى.</p><h2>٥. سلوك المستخدم</h2><p>توافق على استخدام التطبيق وفقًا لجميع القوانين واللوائح المعمول بها.</p><h2>٦. حدود المسؤولية</h2><p>لن تتحمل TAB3 المسؤولية عن أي أضرار غير مباشرة أو تبعية ناتجة عن استخدامك للتطبيق.</p><h2>٧. اتصل بنا</h2><p>للاستفسارات، تواصل معنا على support@tab3.app</p>',
                'content' => '<h1>Terms &amp; Conditions</h1><p>Last updated: February 2026</p><h2>1. Acceptance of Terms</h2><p>By accessing and using <strong>TAB3</strong>, you accept and agree to be bound by these Terms and Conditions.</p><h2>2. User Accounts</h2><p>You are responsible for maintaining the confidentiality of your account credentials.</p><h2>3. Bookings</h2><p>All bookings are subject to availability. Once a booking is confirmed, you will receive a QR code for entry. Cancellation policies vary by cafe.</p><h2>4. Payments</h2><p>All payments are processed securely. Refunds are subject to the cafe\'s cancellation policy.</p><h2>5. User Conduct</h2><p>You agree to use the application in accordance with all applicable laws and regulations.</p><h2>6. Limitation of Liability</h2><p>TAB3 shall not be liable for any indirect or consequential damages resulting from your use of the application.</p><h2>7. Contact</h2><p>For questions, contact us at support@tab3.app</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'title_ar' => 'الشروط والأحكام',
                'content_ar' => '<h1>الشروط والأحكام</h1><p>مرحبًا بك في <strong>TAB3</strong>. باستخدامك لتطبيقنا فإنك تقبل الالتزام بهذه الشروط والأحكام.</p><h2>الحجوزات</h2><p>تخضع جميع الحجوزات للتوفّر. يجب إتمام الدفع لتأكيد الحجز. تُحدَّد سياسات الإلغاء من قِبل كل مقهى.</p><h2>المدفوعات</h2><p>تتم معالجة جميع المدفوعات بأمان. تخضع المبالغ المستردة لسياسة الإلغاء الخاصة بالمقهى.</p><h2>سلوك المستخدم</h2><p>توافق على استخدام التطبيق بشكل قانوني. يُمنع منعًا باتًا إنشاء حسابات وهمية والتلاعب بالحجوزات ومشاركة رموز QR.</p><h2>حدود المسؤولية</h2><p>TAB3 غير مسؤولة عن المشكلات الناشئة عن خدمات المقاهي على المنصة.</p><h2>اتصل بنا</h2><p>للاستفسارات، تواصل معنا على support@tab3.app</p>',
                'content' => '<h1>Terms &amp; Conditions</h1><p>Welcome to <strong>TAB3</strong>. By using our application, you accept and agree to be bound by these Terms and Conditions.</p><h2>Bookings</h2><p>All bookings are subject to availability. Payments must be completed to confirm a booking. Cancellation policies are set by individual cafes.</p><h2>Payments</h2><p>All payments are processed securely. Refunds are subject to the cafe\'s cancellation policy.</p><h2>User Conduct</h2><p>You agree to use the application lawfully. Fake accounts, booking manipulation, and QR code sharing are strictly prohibited.</p><h2>Limitation of Liability</h2><p>TAB3 is not responsible for issues arising from cafe services on the platform.</p><h2>Contact</h2><p>For questions, contact us at support@tab3.app</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'data-usage',
                'title' => 'Data Usage Policy',
                'title_ar' => 'سياسة استخدام البيانات',
                'content_ar' => '<h1>سياسة استخدام البيانات</h1><p>آخر تحديث: فبراير ٢٠٢٦</p><h2>١. نظرة عامة</h2><p>توضّح هذه السياسة كيف تجمع <strong>TAB3</strong> بياناتك وتعالجها وتخزّنها.</p><h2>٢. جمع البيانات</h2><ul><li><strong>بيانات الحساب:</strong> الاسم والبريد الإلكتروني ورقم الهاتف</li><li><strong>بيانات الاستخدام:</strong> تفاعلات التطبيق وسجل الحجوزات</li><li><strong>بيانات الجهاز:</strong> نوع الجهاز ونظام التشغيل</li><li><strong>بيانات الموقع:</strong> بموافقتك، لاقتراح المقاهي القريبة</li></ul><h2>٣. تخزين البيانات</h2><p>يتم تخزين بياناتك بأمان. نحتفظ ببياناتك فقط للمدة اللازمة لتقديم خدماتنا.</p><h2>٤. مشاركة البيانات</h2><p>لا نبيع بياناتك الشخصية. نشاركها فقط مع المقاهي الشريكة لإتمام الحجز ومع معالجات الدفع لإجراء المعاملات.</p><h2>٥. حقوقك</h2><p>لديك الحق في الوصول إلى بياناتك الشخصية أو تصحيحها أو حذفها. تواصل معنا على support@tab3.app</p>',
                'content' => '<h1>Data Usage Policy</h1><p>Last updated: February 2026</p><h2>1. Overview</h2><p>This policy explains how <strong>TAB3</strong> collects, processes, and stores your data.</p><h2>2. Data Collection</h2><ul><li><strong>Account Data:</strong> Name, email, phone number</li><li><strong>Usage Data:</strong> App interactions and booking history</li><li><strong>Device Data:</strong> Device type and operating system</li><li><strong>Location Data:</strong> With your consent, for nearby cafe suggestions</li></ul><h2>3. Data Storage</h2><p>Your data is stored securely. We retain your data only as long as necessary to provide our services.</p><h2>4. Data Sharing</h2><p>We do not sell your personal data. We share only with cafe partners for booking fulfillment and payment processors for transactions.</p><h2>5. Your Rights</h2><p>You have the right to access, correct, or delete your personal data. Contact us at support@tab3.app</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'usage-policy',
                'title' => 'Usage Policy',
                'title_ar' => 'سياسة الاستخدام',
                'content_ar' => '<h1>سياسة الاستخدام</h1><p>باستخدامك <strong>TAB3</strong> فإنك توافق على شروط الاستخدام المقبول التالية.</p><h2>الاستخدام المقبول</h2><p>توافق على تقديم معلومات دقيقة، واستخدام التطبيق بشكل قانوني، واحترام موظفي المقهى والحضور الآخرين.</p><h2>الأنشطة المحظورة</h2><ul><li>إنشاء حسابات أو حجوزات وهمية</li><li>إعادة بيع الحجوزات المشتراة عبر TAB3</li><li>استخدام أدوات آلية لإجراء الحجوزات</li><li>مضايقة المستخدمين الآخرين أو موظفي المقهى</li></ul><h2>سياسة رمز QR</h2><p>كل رمز QR صالح لتسجيل دخول واحد فقط. تُمنع منعًا باتًا مشاركته أو تكراره أو نقله، وقد يؤدي ذلك إلى تعليق الحساب فورًا.</p><h2>تعليق الحساب</h2><p>تحتفظ TAB3 بالحق في تعليق أو إنهاء الحسابات التي تنتهك هذه السياسة دون إشعار مسبق.</p>',
                'content' => '<h1>Usage Policy</h1><p>By using <strong>TAB3</strong>, you agree to the following terms of acceptable use.</p><h2>Acceptable Use</h2><p>You agree to provide accurate information, use the app lawfully, and respect cafe staff and other attendees.</p><h2>Prohibited Activities</h2><ul><li>Creating fake accounts or bookings</li><li>Reselling bookings purchased through TAB3</li><li>Using automated tools to make bookings</li><li>Harassing other users or cafe staff</li></ul><h2>QR Code Policy</h2><p>Each QR code is valid for a single check-in only. Sharing, duplicating, or transferring QR codes is strictly prohibited and may result in immediate account suspension.</p><h2>Account Suspension</h2><p>TAB3 reserves the right to suspend or terminate accounts that violate this policy without prior notice.</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'cookie-policy',
                'title' => 'Cookie Policy',
                'title_ar' => 'سياسة ملفات تعريف الارتباط',
                'content_ar' => '<h1>سياسة ملفات تعريف الارتباط</h1><p>آخر تحديث: فبراير ٢٠٢٦</p><h2>١. ما هي ملفات تعريف الارتباط</h2><p>ملفات تعريف الارتباط هي ملفات نصية صغيرة تُخزَّن على جهازك عند استخدام خدماتنا.</p><h2>٢. كيف نستخدم ملفات تعريف الارتباط</h2><ul><li><strong>المصادقة:</strong> لإبقائك مسجّل الدخول</li><li><strong>التفضيلات:</strong> لتذكّر إعداداتك</li><li><strong>التحليلات:</strong> لفهم كيفية استخدام خدماتنا</li><li><strong>الأمان:</strong> لاكتشاف الأنشطة الاحتيالية ومنعها</li></ul><h2>٣. إدارة ملفات تعريف الارتباط</h2><p>يمكنك التحكم في ملفات تعريف الارتباط من خلال إعدادات متصفحك. قد يؤثر تعطيل بعضها على وظائف التطبيق.</p><h2>٤. ملفات تعريف الارتباط من جهات خارجية</h2><p>قد يضع بعض مزوّدي الخدمات ملفات تعريف ارتباط على جهازك، وتخضع لسياسات الخصوصية الخاصة بهم.</p><h2>٥. اتصل بنا</h2><p>لأي استفسارات حول استخدامنا لملفات تعريف الارتباط، تواصل معنا على support@tab3.app</p>',
                'content' => '<h1>Cookie Policy</h1><p>Last updated: February 2026</p><h2>1. What Are Cookies</h2><p>Cookies are small text files stored on your device when you use our services.</p><h2>2. How We Use Cookies</h2><ul><li><strong>Authentication:</strong> To keep you signed in</li><li><strong>Preferences:</strong> To remember your settings</li><li><strong>Analytics:</strong> To understand how our services are used</li><li><strong>Security:</strong> To detect and prevent fraudulent activity</li></ul><h2>3. Managing Cookies</h2><p>You can control cookies through your browser settings. Disabling certain cookies may affect functionality.</p><h2>4. Third-Party Cookies</h2><p>Some service providers may place cookies on your device, governed by their own privacy policies.</p><h2>5. Contact</h2><p>For questions about our use of cookies, contact us at support@tab3.app</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'cookies-policy',
                'title' => 'Cookies Policy',
                'title_ar' => 'سياسة ملفات تعريف الارتباط',
                'content_ar' => '<h1>سياسة ملفات تعريف الارتباط</h1><p>تستخدم <strong>TAB3</strong> ملفات تعريف الارتباط والتقنيات المشابهة لتحسين تجربتك.</p><h2>أنواع ملفات تعريف الارتباط التي نستخدمها</h2><ul><li><strong>ملفات أساسية:</strong> ضرورية للمصادقة وإدارة الجلسات</li><li><strong>ملفات التحليلات:</strong> تساعدنا على فهم كيفية تفاعل المستخدمين مع التطبيق</li><li><strong>ملفات التفضيلات:</strong> تتذكّر إعداداتك وتفضيلاتك</li></ul><h2>إدارة ملفات تعريف الارتباط</h2><p>يمكنك التحكم في ملفات تعريف الارتباط من خلال إعدادات جهازك أو متصفحك. قد يؤثر تعطيل الملفات الأساسية على وظائف التطبيق.</p><h2>ملفات تعريف الارتباط من جهات خارجية</h2><p>قد تستخدم بعض الميزات ملفات تعريف ارتباط من جهات خارجية تخضع لسياسات الخصوصية الخاصة بكل مزوّد.</p><h2>التحديثات</h2><p>استمرارك في استخدام TAB3 بعد إجراء تغييرات على هذه السياسة يُعدّ قبولًا للشروط المحدَّثة.</p>',
                'content' => '<h1>Cookies Policy</h1><p><strong>TAB3</strong> uses cookies and similar technologies to improve your experience.</p><h2>Types of Cookies We Use</h2><ul><li><strong>Essential Cookies:</strong> Required for authentication and session management</li><li><strong>Analytics Cookies:</strong> Help us understand how users interact with the app</li><li><strong>Preference Cookies:</strong> Remember your settings and preferences</li></ul><h2>Managing Cookies</h2><p>You can control cookies through your device or browser settings. Disabling essential cookies may affect app functionality.</p><h2>Third-Party Cookies</h2><p>Some features may use third-party cookies governed by the respective provider\'s privacy policies.</p><h2>Updates</h2><p>Continued use of TAB3 after changes to this policy constitutes acceptance of the updated terms.</p>',
                'is_active' => true,
            ],
            [
                'slug' => 'faq',
                'title' => 'Frequently Asked Questions',
                'title_ar' => 'الأسئلة الشائعة',
                'content_ar' => '<h1>الأسئلة الشائعة</h1><h2>ما هو TAB3؟</h2><p>TAB3 منصة تتيح لك حجز مقاعد في المقاهي لمشاهدة المباريات الرياضية المباشرة في أجواء رائعة.</p><h2>كيف أحجز مقعدًا؟</h2><p>تصفّح المباريات القادمة، واختر مقاعدك، وأكمل الدفع، وستتلقى تأكيد الحجز مع رمز QR.</p><h2>كيف أسجّل الدخول؟</h2><p>اعرض رمز QR عند مدخل المقهى. سيقوم الموظفون بمسحه لتسجيل دخولك. كل رمز QR صالح لتسجيل دخول واحد فقط.</p><h2>هل يمكنني إلغاء حجزي؟</h2><p>نعم، تخضع عمليات الإلغاء لسياسة الإلغاء الخاصة بالمقهى والمعروضة قبل الحجز.</p><h2>هل دفعي آمن؟</h2><p>نعم، تتم معالجة جميع المدفوعات عبر بوابات دفع مشفّرة وآمنة.</p><h2>كيف أتواصل مع الدعم؟</h2><p>استخدم قسم الدعم في تطبيق TAB3 لإرسال تذكرة أو الإبلاغ عن مشكلة.</p>',
                'content' => '<h1>Frequently Asked Questions</h1><h2>What is TAB3?</h2><p>TAB3 is a platform that lets you book seats at cafes to watch live sports matches in a great atmosphere.</p><h2>How do I book a seat?</h2><p>Browse upcoming matches, select your seats, complete payment, and receive your booking confirmation with a QR code.</p><h2>How do I check in?</h2><p>Present your QR code at the cafe entrance. Staff will scan it to check you in. Each QR code is valid for one check-in only.</p><h2>Can I cancel my booking?</h2><p>Yes, cancellations are subject to the cafe\'s cancellation policy shown before booking.</p><h2>Is my payment secure?</h2><p>Yes, all payments are processed through encrypted, secure payment gateways.</p><h2>How do I contact support?</h2><p>Use the Support section in the TAB3 app to submit a ticket or report an issue.</p>',
                'is_active' => true,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
