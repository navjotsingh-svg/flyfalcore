<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $subscribers = NewsletterSubscriber::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('email', 'like', '%'.$request->string('q')->toString().'%');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.subscribers.index', compact('subscribers'));
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back()->with('success', 'Subscriber removed.');
    }
}
