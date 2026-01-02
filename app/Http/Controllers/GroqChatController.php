<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Knowledgebase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqChatController extends Controller
{
    /**
     * Chat using Groq's OpenAI-compatible endpoint.
     */
    public function chat(Request $request)
    {
        $userInput = $request->input('message');

        $chatHistory = session()->get('groq_chat_history', []);

        $chatHistory[] = ['role' => 'user', 'text' => $userInput];

        $knowledgebaseData = Knowledgebase::all()
            ->map(fn($entry) => "{$entry->title}: {$entry->content}")
            ->implode("\n");

        $systemPrompt = "
            You are a helpful, friendly, and knowledgeable volunteer of Kalinga ng Kababaihan.
            Use the information provided below (the KNOWLEDGEBASE) to answer questions accurately, naturally, and professionally.

            --- IMPORTANT RULES ---
            1. Do NOT mention or refer to the 'knowledgebase' itself.
            2. Only give information about Kalinga ng Kababaihan when the user directly asks or when it is clearly relevant.
            3. Do NOT invent information that is not found in the knowledgebase.
            4. If the question is not answerable using the knowledgebase, respond with:
               \"I'm sorry, I don't have an answer right now.\"
            5. Keep responses conversational, warm, and helpful—sound like a real volunteer.
            6. Do NOT reintroduce yourself unless the user specifically asks.
            7. Avoid repeating greetings more than once in the same conversation.
            8. If the user asks general questions (not about us), give a simple helpful answer IF it is basic general knowledge.
               Otherwise say: \"I'm sorry, I don't have an answer right now.\"
            9. If the user asks about Kalinga ng Kababaihan, explain clearly and naturally, not in bullet dumps unless requested.
            10. Keep every response concise, friendly, and human-sounding.

            --- KNOWLEDGEBASE START ---
            $knowledgebaseData
            --- KNOWLEDGEBASE END ---
        ";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($chatHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'model' ? 'assistant' : 'user',
                'content' => $msg['text'],
            ];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.1-8b-instant',
                'messages' => $messages,
            ]);

            Log::info('Groq API Response:', ['response' => $response->body()]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['choices'][0]['message']['content'])) {
                    $botMessage = $responseData['choices'][0]['message']['content'];
                } else {
                    $botMessage = "I'm sorry, I don't have an answer right now.";
                }

                $chatHistory[] = ['role' => 'model', 'text' => $botMessage];
                session()->put('groq_chat_history', $chatHistory);

                return response()->json(['message' => $botMessage]);
            }

            Log::error('Groq API Error', ['response' => $response->body()]);
            return response()->json(['error' => $response->body()], 500);
        } catch (\Exception $e) {
            Log::error('Groq API Request Failed', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}
