<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class GoogleSuggestionsController extends AbstractController
{
    /**
     * @Route("/api/track-suggestion", name="api_track_suggestion", methods={"POST"})
     */
    public function trackSuggestion(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Log the suggestion click for analytics
        $this->addFlash('info', 'Suggestion tracked: ' . ($data['suggestion'] ?? 'unknown'));
        
        // Here you would normally save to database or analytics service
        // For now, we'll just log it
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Suggestion tracked successfully'
        ]);
    }

    /**
     * @Route("/api/create-task", name="api_create_task", methods={"POST"})
     */
    public function createTask(Request $request): JsonResponse
    {
        $data = $request->request->all();
        
        // Validate required fields
        if (empty($data['title'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Title is required'
            ], 400);
        }
        
        // Here you would normally save the task to database
        // For now, we'll just simulate success
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Task created successfully',
            'task_id' => rand(1000, 9999) // Random ID for demo
        ]);
    }

    /**
     * @Route("/api/weather", name="api_weather", methods={"GET"})
     */
    public function getWeather(): JsonResponse
    {
        // Simulate weather data
        $weatherData = [
            'temperature' => 28,
            'humidity' => 65,
            'wind_speed' => 12,
            'condition' => 'sunny',
            'description' => 'Ensoleillé',
            'icon' => 'sun',
            'recommendation' => 'Conditions idéales pour les travaux agricoles aujourd\'hui'
        ];
        
        return new JsonResponse($weatherData);
    }
}
