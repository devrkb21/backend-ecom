<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicSettingResource;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class FrontendSettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Get all public settings (for frontend)
     */
    public function index(): JsonResponse
    {
        $settings = $this->settingService->getAllPublicSettings();

        return $this->successResponse(
            PublicSettingResource::formatGrouped($settings)
        );
    }

    /**
     * Get settings for a specific group (for frontend)
     */
    public function showGroup(string $group): JsonResponse
    {
        $settings = $this->settingService->getPublicSettingsByGroup($group);

        return $this->successResponse(
            PublicSettingResource::formatGroup($settings)
        );
    }

    /**
     * Get hero section settings
     */
    public function hero(): JsonResponse
    {
        $settings = $this->settingService->getPublicSettingsByGroup('hero');

        return $this->successResponse(
            PublicSettingResource::formatGroup($settings)
        );
    }

    /**
     * Get general site settings
     */
    public function general(): JsonResponse
    {
        $settings = $this->settingService->getPublicSettingsByGroup('general');

        return $this->successResponse(
            PublicSettingResource::formatGroup($settings)
        );
    }

    /**
     * Get social media settings
     */
    public function social(): JsonResponse
    {
        $settings = $this->settingService->getPublicSettingsByGroup('social');

        return $this->successResponse(
            PublicSettingResource::formatGroup($settings)
        );
    }

    /**
     * Get SEO settings
     */
    public function seo(): JsonResponse
    {
        $settings = $this->settingService->getPublicSettingsByGroup('seo');

        return $this->successResponse(
            PublicSettingResource::formatGroup($settings)
        );
    }

    /**
     * Get footer settings
     */
    public function footer(): JsonResponse
    {
        $settings = $this->settingService->getPublicSettingsByGroup('footer');

        return $this->successResponse(
            PublicSettingResource::formatGroup($settings)
        );
    }

    /**
     * Get banner/promo settings
     */
    public function banner(): JsonResponse
    {
        $settings = $this->settingService->getPublicSettingsByGroup('banner');

        return $this->successResponse(
            PublicSettingResource::formatGroup($settings)
        );
    }
}
