<?php

namespace App\Http\Controllers;

use App\Models\Page;

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
}
