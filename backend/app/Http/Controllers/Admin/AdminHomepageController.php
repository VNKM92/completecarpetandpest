<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteSetting;
use App\Models\Page;
use App\Services\ActivityLogger;

class AdminHomepageController extends Controller
{
    private const DEFAULT_HOMEPAGE_SECTIONS = [
        'hero' => [
            'badge' => "Brisbane's #1 Bond & Carpet Specialists",
            'heading' => 'Expert Carpet Cleaning & Pest Control in Brisbane',
            'subheading' => 'Professional end of lease cleaning, carpet steam cleaning, and comprehensive pest management backed by our 100% Bond Back Guarantee.',
            'ratingScore' => '4.9',
            'ratingCount' => '200+ Reviews',
            'primaryCtaText' => 'Get Instant Quote',
            'primaryCtaLink' => '/request-estimate',
            'secondaryCtaText' => 'Explore Services',
            'secondaryCtaLink' => '/services',
            'phoneDisplay' => '0434 061 188',
            'phoneTel' => '0434061188',
            'heroImage' => '/chairwithblancket.png',
        ],
        'homeAbout' => [
            'sloganBadge' => 'About us',
            'title' => 'Honest. Simple.',
            'titleHighlight' => 'Spotless.',
            'description' => "Before your first house cleaning service, we'll take the time to talk about your preferences and priorities with you and combine them with cleaning techniques to give your home the greatest possible clean.",
            'satisfactionRate' => '96%',
            'satisfactionReviewCount' => '356 reviews on Google',
            'aboutImage' => '/assets/home/image/home-about.png',
            'cards' => [
                ['title' => 'Trust', 'desc' => 'Trust is our paramount value. All of our employees go through work authorization check.'],
                ['title' => 'Quality', 'desc' => 'Excellent performance, flat rates, no surprises. Five star rating on Google.'],
                ['title' => 'Care', 'desc' => 'Average response time is less than 10 minutes. You can call, e-mail, text or message us.'],
                ['title' => 'People', 'desc' => 'We pay good wages, health benefits and retirement, and abide by the laws.'],
            ],
        ],
        'estimateSection' => [
            'title' => 'Get a Quick Estimate',
            'subtitle' => '*For a detailed quote, use extended version',
            'step1Title' => 'Book Consultations',
            'step1Desc' => 'Tell us what type of cleaning service you need, the size of your home or space and preferred date and time.',
            'step2Title' => 'Choose Package',
            'step2Desc' => "We'll provide a price and time estimate for the cleaning as well as available time slots that match your schedule.",
            'step3Title' => 'We Clean, You Relax',
            'step3Desc' => 'Our professional team will arrive on time with all supplies and perform a detailed cleaning as estimate agreed.',
        ],
        'mainCard' => [
            'badge' => 'Professional Care',
            'title' => 'Why Choose Brisbane Carpet & Pest Experts',
            'description' => 'Top-tier commercial equipment, eco-safe solutions, and certified professionals delivering flawless cleaning for residential and commercial properties across Brisbane.',
            'bannerImage' => '/assets/home/image/reliable-cleaning-experts.png',
        ],
        'whatCanWeClean' => [
            'heading' => 'What Can We Clean For You Today?',
            'subheading' => 'Book Your Clean Now - 100% Satisfaction & Bond Back Guarantee',
            'phone' => '0434 061 188',
            'phoneTel' => '0434061188',
            'cleanerImage' => '/assets/home/we-are.png',
            'awardBadgeImage' => '/assets/home/100-Satisfaction.png',
        ],
        'seo' => [
            'metaTitle' => 'Brisbane Carpet & Pest Experts | Top-Rated Bond Cleaning Brisbane',
            'metaDesc' => 'Professional cleaning services in Brisbane including bond cleaning, end-of-lease, carpet steam cleaning, and pest control. 100% Satisfaction & Bond Back Guaranteed.',
            'metaKeywords' => 'carpet cleaning brisbane, bond cleaning, pest control brisbane, end of lease cleaning, commercial cleaning',
            'canonicalUrl' => 'http://localhost:3000',
            'ogImage' => '/images/og-image.jpg',
            'robots' => 'index, follow',
        ],
    ];

    public function index()
    {
        $setting = SiteSetting::where('key', 'homepage_sections')->first();
        $homePage = Page::where('slug', 'home')->first();

        $sections = self::DEFAULT_HOMEPAGE_SECTIONS;
        if ($setting && $setting->value) {
            $parsed = json_decode($setting->value, true);
            if (is_array($parsed)) {
                $sections = array_merge($sections, $parsed);
            }
        }

        if ($homePage) {
            $sections['seo'] = [
                'metaTitle' => $homePage->meta_title ?: $sections['seo']['metaTitle'],
                'metaDesc' => $homePage->meta_desc ?: $sections['seo']['metaDesc'],
                'metaKeywords' => $homePage->meta_keywords ?: $sections['seo']['metaKeywords'],
                'canonicalUrl' => $homePage->canonical_url ?: $sections['seo']['canonicalUrl'],
                'ogImage' => $homePage->og_image ?: $sections['seo']['ogImage'],
                'robots' => $homePage->robots ?: $sections['seo']['robots'],
            ];
        }

        return response()->json(['success' => true, 'data' => $sections]);
    }

    public function store(Request $request)
    {
        $sections = $request->input('sections');
        if (!$sections) {
            return response()->json(['success' => false, 'message' => 'Missing sections payload'], 400);
        }

        SiteSetting::updateOrCreate(
            ['key' => 'homepage_sections'],
            [
                'value' => json_encode($sections),
                'group' => 'homepage',
                'label' => 'Homepage Dynamic Sections CMS',
            ]
        );

        if (!empty($sections['seo'])) {
            Page::updateOrCreate(
                ['slug' => 'home'],
                [
                    'title' => 'Home Page',
                    'meta_title' => $sections['seo']['metaTitle'] ?? null,
                    'meta_desc' => $sections['seo']['metaDesc'] ?? null,
                    'meta_keywords' => $sections['seo']['metaKeywords'] ?? null,
                    'canonical_url' => $sections['seo']['canonicalUrl'] ?? null,
                    'og_image' => $sections['seo']['ogImage'] ?? null,
                    'robots' => $sections['seo']['robots'] ?? 'index, follow',
                    'is_published' => true,
                ]
            );
        }

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Homepage CMS',
            entityId: 'homepage_sections',
            details: 'Updated Homepage Sections and SEO configurations',
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Homepage content and SEO updated successfully!',
        ]);
    }
}
