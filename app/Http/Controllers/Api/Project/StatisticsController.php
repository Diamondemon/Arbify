<?php

namespace Arbify\Http\Controllers\Api\Project;

use Arbify\Contracts\Repositories\MessagesTranslationStatisticsRepository;
use Arbify\Http\Controllers\BaseController;
use Arbify\Contracts\Repositories\LanguageRepository;
use Arbify\Contracts\Repositories\ProjectRepository;
use Arbify\Http\Requests\AddLanguagesToProject;
use Arbify\Models\Language;
use Arbify\Models\Project;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StatisticsController extends BaseController
{
    private LanguageRepository $languageRepository;
    private ProjectRepository $projectRepository;

    public function __construct(
        LanguageRepository $languageRepository,
        ProjectRepository $projectRepository
    ) {
        $this->languageRepository = $languageRepository;
        $this->projectRepository = $projectRepository;

        $this->middleware('guest');
        //$this->authorizeResource(Project::class);
    }

    public function index(
        Project $project,
        MessagesTranslationStatisticsRepository $statisticsRepository
    ): Response {
        $languages = $this->languageRepository->allInProject($project);
        $statistics = [];
        foreach ($languages as $language) {
            $statistics[$language->code] = $statisticsRepository->byProjectAndLanguage($project, $language);
            $statistics[$language->code]["percent"] = $statistics[$language->code]["percent"] . "%";
        }

        return response()->json([
            //'project' => $project,
            //'languages' => $languages,
            'statistics' => $statistics,
        ]);
    }
}
