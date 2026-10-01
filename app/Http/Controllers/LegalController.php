<?php

namespace App\Http\Controllers;

class LegalController extends Controller
{
    public function privacy()
    {
        return $this->page('legal.privacy');
    }

    public function terms()
    {
        return $this->page('legal.terms');
    }

    public function dataDeletion()
    {
        return $this->page('legal.data-deletion');
    }

    protected function page(string $view)
    {
        return response()
            ->view($view, [
                'appName' => config('crm.name', config('app.name')),
                'contactEmail' => config('crm.privacy_email'),
                'updatedAt' => 'August 31, 2026',
            ])
            ->header('Cache-Control', 'public, max-age=300');
    }
}
