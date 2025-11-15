<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Knowledgebase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ChatBotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function chat(Request $request)
    {
        $userInput = $request->input('message');

        // Retrieve previous chat history from session
        $chatHistory = session()->get('chat_history', []);

        // Append user input to chat history
        $chatHistory[] = ["role" => "user", "text" => $userInput];

        $knowledgebaseData = Knowledgebase::all()->map(fn($entry) => "{$entry->title}: {$entry->content}")->implode("\n");

        // Define the knowledge base (removing 'system' role)
        // $knowledgebase = "KNOWLEDGEBASE:\n\n" . $knowledgebaseData . "\n\n" . "
        //                 You will be responding to the incoming question, don't give anything about us yet if no direct questions about us is asked but offer to be asked about us, answer as if you ar one of us, like you are a volunteer of kalinga ng kababaihan, respond as professional, answer if needed, if ever the question is not about whatever in the knowledge base, simply responed 'I'am sorry, I don't have an answer right now.' don't mention the phrase 'knowledge base' provided to you, if the question is about us and answerable through the knowledge base, please don't answer plainly,
        //                 make it casual but professional, again don't give information unless asked and don't send greetings if you already sent one, also, stop re introducing yourself once you did already unless spicifically asked. Now, respond to the incoming question strictly based on the knowledge base nothing else:";

        $knowledgebase = "
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

        // Construct conversation (removing 'system' role)
        $conversation = array_merge([["role" => "user", "text" => $knowledgebase]], $chatHistory);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => env('GEMINI_API_KEY'),
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
                'contents' => array_map(fn($msg) => [
                    'role' => $msg['role'] ?? 'user',
                    'parts' => [['text' => $msg['text']]],
                ], $conversation),
            ]);

            // Log the raw response for debugging
            Log::info('Gemini API Response:', ['response' => $response->body()]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                    $botMessage = $responseData['candidates'][0]['content']['parts'][0]['text'];
                } else {
                    $botMessage = "I'm sorry, I don't have an answer right now.";
                }

                // Append AI response to chat history
                $chatHistory[] = ["role" => "model", "text" => $botMessage];

                // Store updated history in session
                session()->put('chat_history', $chatHistory);

                return response()->json(["message" => $botMessage]);
            } else {
                Log::error('Gemini API Error', ['response' => $response->body()]);
                return response()->json(['error' => 'Failed to generate a response'], 500);
            }
        } catch (\Exception $e) {
            Log::error('API Request Failed', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

}


// Address: B4 LOT6-6 FANTACY ROAD 3 TERESA PARK SUBD. PILAR LAS PINAS CITY SEC REG. NO:2024100171937-10
// What we are: Kalinga ng Kbabaihan, A self-sustaining non-governmental organization that aims to promote a sense of community and cooperation among like-minded and self-sufficiency-seeking individuals to contribute to the betterment of the society.
// Vision: Empowered and united women through volunteerism towards community resilliency and development.
// Mission: To promote and strengthen the physical and social well-being of children and senior members of the community, through nutrition programs, responding to emergencies and calamities.
// Contact:
// Landline number: 0283742811
// Cell phone number: 09209859508
// Facebook Page: https://www.facebook.com/kalingangkababaihanwllpc?mibextid=ZbWkwL
// Emeail address: kalingangkababaihan.wllpc@gmail.com
