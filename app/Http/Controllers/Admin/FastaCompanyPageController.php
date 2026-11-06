<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FastaCompanyPages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FastaCompanyPageController extends Controller
{
    public function edit(FastaCompanyPages $company, string $page = 'home')
    {
        $pages = $company->all();
        abort_unless(isset($pages[$page]), 404);

        return view('admin-views.business-settings.fasta-company', [
            'pages' => $pages,
            'slug' => $page,
            'page' => $pages[$page],
            'actions' => FastaCompanyPages::ACTIONS,
            'previewUrl' => $company->url($page),
        ]);
    }

    public function update(Request $request, FastaCompanyPages $company, string $page)
    {
        abort_unless(array_key_exists($page, config('fasta-company')), 404);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'intro' => ['nullable', 'string', 'max:1200'],
            'published' => ['required', 'boolean'],
            'header' => ['required', 'boolean'],
            'footer' => ['required', 'boolean'],
            'cta' => ['nullable', Rule::in(array_keys(FastaCompanyPages::ACTIONS))],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'sections' => ['nullable', 'array', 'max:8'],
            'sections.*.heading' => ['nullable', 'required_with:sections.*.body', 'string', 'max:120'],
            'sections.*.body' => ['nullable', 'required_with:sections.*.heading', 'string', 'max:2500'],
        ]);
        $data['sections'] = array_values(array_filter($data['sections'] ?? [], fn (array $section) => filled($section['heading'] ?? null)));
        $data['cta'] = $data['cta'] ?? '';
        $company->save($page, $data);

        return back()->with('company_saved', 'Page settings saved.');
    }
}
