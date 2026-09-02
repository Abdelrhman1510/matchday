<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicPageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::active()->bySlug($slug)->firstOrFail();

        return view('public.page', compact('page'));
    }

    public function privacyPolicy()
    {
        return $this->show('privacy-policy');
    }

    public function accountDeletion()
    {
        return view('public.account-deletion');
    }

    public function landing()
    {
        return view('public.landing');
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function submitContact(Request $request)
    {
        // Honeypot: bots fill hidden fields; humans don't.
        if ($request->filled('website')) {
            return back()->with('success', 'Thanks — we\'ll be in touch.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $to = config('mail.from.address');
        $body = "Name: {$data['name']}\nEmail: {$data['email']}\nSubject: "
            . ($data['subject'] ?: '(none)') . "\n\n{$data['message']}";

        try {
            Mail::raw($body, function ($m) use ($to, $data) {
                $m->to($to)
                    ->replyTo($data['email'], $data['name'])
                    ->subject('Shaj3 contact: ' . ($data['subject'] ?: 'New message'));
            });
        } catch (\Throwable $e) {
            \Log::warning('Contact form send failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Could not send right now. Please email us directly.');
        }

        return back()->with('success', 'Thanks — your message was sent. We\'ll reply soon.');
    }
}
