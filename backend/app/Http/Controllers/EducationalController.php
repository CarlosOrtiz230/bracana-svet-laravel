<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Str;
use App\Models\ZapScan;
use App\Models\NiktoScan;
use OpenAI;
use Illuminate\Support\Facades\Log;
use App\Models\SemgrepScan;
use App\Http\Controllers\SemgrepScanController;



class EducationalController extends Controller
{
    public function generateGuidance(Request $request)
{
    $results = $request->input('results', []);
    $tool = $request->input('tool', 'unknown');

    $metricsController = new MetricsController();
    $metricResponse = $metricsController->analyze(new Request([
        'results' => $results,
        'tool' => $tool
    ]))->getData(true); // parse JSON to array

    $guidance = [];

    foreach ($results as $index => $item) {
        $severity = $this->extractSeverity($item, $tool);
        $title = $item['alert'] ?? $item['message'] ?? 'Unknown issue';

        $guidance[] = [
            'title' => $title,
            'severity' => ucfirst($severity),
            'explanation' => $this->generateExplanation($item, $tool),
            'recommendation' => $this->generateFix($item, $tool),
            'owasp_reference' => $tool === 'zap' ? $this->owaspReferenceLink($title) : null,
            'custom_explanation' => $tool === 'nikto' ? $this->customNiktoExplanation($item['msg'] ?? '') : null,
        ];
    }

    return response()->json([
        'tool' => $tool,
        'metrics' => $metricResponse,
        'guidance' => $guidance
    ]);
}


    protected function extractSeverity(array $item, string $tool): string
    {
        return match ($tool) {
            'zap' => strtolower(trim(strtok($item['riskdesc'] ?? 'unknown', ' '))),
            'nikto' => MetricsController::guessNiktoSeverity($item),

            'semgrep' => strtolower($item['severity'] ?? 'low'),
            'codeql' => strtolower($item['severity'] ?? 'warning'),
            default => 'informational'
        };
    }

    protected function generateExplanation(array $item, string $tool): string
    {
        return match ($tool) {
            'zap' => $item['desc'] ?? 'No explanation available.',
            'nikto' => $item['msg'] ?? 'Generic server misconfiguration.',
            'semgrep', 'codeql' => $item['extra']['message'] ?? 'Static code issue detected.',
            default => 'Unknown tool — no details.'
        };
    }

    protected function generateFix(array $item, string $tool): string
    {
        return match ($tool) {
            'zap' => $item['solution'] ?? 'Review the recommendation in the ZAP report.',
            'nikto' => 'Inspect the server headers or software version exposed.',
            'semgrep' => $item['fix'] ?? 'Check coding best practices or linting rules.',
            'codeql' => 'Examine code flow for potential logic or security flaws.',
            default => 'No fix available.'
        };
    }
 

    public function educateFromStorage($tool, $id)
    {
        switch ($tool) {
            case 'zap':
                $scan = ZapScan::findOrFail($id);
                break;
            case 'nikto':
                $scan = NiktoScan::findOrFail($id);
                break;
            case 'semgrep':
                $scan = SemgrepScan::findOrFail($id); // ✅ Add support for Semgrep
                break;
            default:
                abort(404, 'Unknown scan tool');
        }

        $results = is_string($scan->findings) ? json_decode($scan->findings, true) : $scan->findings;

        $metricsController = new MetricsController();
        $metricResponse = $metricsController->analyze(new Request([
            'results' => $results,
            'tool' => $tool
        ]))->getData(true);

        $guidanceResponse = $this->generateGuidance(new Request([
            'results' => $results,
            'tool' => $tool
        ]))->getData(true);

        return view('educational', [
            'tool' => $tool,
            'metrics' => $guidanceResponse['metrics'] ?? [],
            'guidance' => $guidanceResponse['guidance'] ?? [],
        ]);
    }



    //hash map for owasp reference links
    protected function owaspReferenceLink(string $alertName): ?string
    {
        $links = [
            'CORS Misconfiguration' => 'https://cheatsheetseries.owasp.org/cheatsheets/CORS_Validation_Cheat_Sheet.html',
            'Cross-Domain Misconfiguration' => 'https://owasp.org/www-community/cross-site_request_forgery',
            'Content Security Policy (CSP) Header Not Set' => 'https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html',
            'Missing Anti-clickjacking Header' => 'https://cheatsheetseries.owasp.org/cheatsheets/Clickjacking_Defense_Cheat_Sheet.html',
            'X-Frame-Options Header Not Set' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options',
            'X-Content-Type-Options Header Missing' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options',
            'Strict-Transport-Security Header Not Set' => 'https://cheatsheetseries.owasp.org/cheatsheets/Transport_Layer_Protection_Cheat_Sheet.html',
            'Permissions Policy Header Not Set' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Permissions-Policy',
            'Server Leaks Version Information via "Server" HTTP Response Header Field' => 'https://cheatsheetseries.owasp.org/cheatsheets/Information_Leakage.html',
            'Storable and Cacheable Content' => 'https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Headers_Cheat_Sheet.html#cache-control',
            'Secure Flag Not Set on Cookies' => 'https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html#cookies',
            'HttpOnly Flag Not Set on Cookies' => 'https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html#cookies',
            'Set-Cookie Without SameSite Attribute' => 'https://owasp.org/www-community/SameSite',
            'Insufficient Site Isolation Against Spectre Vulnerability' => 'https://web.dev/cross-origin-isolation-guide/',
            'Cross-Site Scripting (Reflected)' => 'https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html',
            'Cross-Site Scripting (Persistent)' => 'https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html',
            'SQL Injection' => 'https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html',
            'Directory Browsing' => 'https://owasp.org/www-community/attacks/Directory_traversal',
            'Missing X-XSS-Protection Header' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection',
            'Information Disclosure - Sensitive Info in URL' => 'https://cheatsheetseries.owasp.org/cheatsheets/Information_Leakage.html',
            'Missing or Insecure Caching Headers' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control',
            'Open Redirect' => 'https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html',
        ];
    
        return $links[$alertName] ?? null;
    }
    
    // Custom explanation for Nikto findings
    // This function provides a more detailed explanation for specific Nikto findings
    // based on the message content. It uses string matching to identify the type of finding
    protected function customNiktoExplanation(string $msg): string
    {
        return match (true) {
            str_contains($msg, 'X-Frame-Options') => 'The X-Frame-Options header prevents clickjacking. Without it, attackers may embed the site in an iframe and trick users into clicking elements.',
            str_contains($msg, 'Server leaks') => 'Leaking server information (e.g. version numbers) gives attackers clues about potential exploits available for your server stack.',
            str_contains($msg, 'X-XSS-Protection') => 'The X-XSS-Protection header enables some browsers to stop pages from loading when they detect reflected cross-site scripting (XSS) attacks.',
            str_contains($msg, 'Allowed HTTP Methods') => 'If HTTP methods like PUT or DELETE are enabled unnecessarily, attackers could exploit them to upload files or modify server resources.',
            str_contains($msg, 'Outdated') || str_contains($msg, 'obsolete') => 'The server appears to be using outdated software, which may contain known vulnerabilities. Consider updating to a supported version.',
            str_contains($msg, 'TRACE method') => 'The TRACE method can be used in Cross Site Tracing attacks, allowing attackers to steal cookies or authentication data.',
            str_contains($msg, 'Directory indexing') || str_contains($msg, 'Index of /') => 'Directory indexing exposes a file listing, which can help attackers find sensitive files or scripts that were not meant to be public.',
            str_contains($msg, 'robots.txt') => 'robots.txt may reveal hidden areas of a site. Attackers often use it to find unprotected admin panels or sensitive endpoints.',
            str_contains($msg, 'SSL') || str_contains($msg, 'TLS') => 'Weak SSL/TLS settings can leave encrypted data exposed to downgrade or man-in-the-middle attacks.',
            str_contains($msg, 'cgi-bin') => 'cgi-bin paths may contain old or vulnerable scripts that attackers can exploit. Ensure scripts are necessary and secure.',
            str_contains($msg, 'authentication') => 'Exposed authentication pages or headers without proper rate limiting can be targeted in brute-force attacks.',
            str_contains($msg, 'cookie') && str_contains($msg, 'Secure') => 'Cookies without the Secure flag can be transmitted over unencrypted channels, exposing them to interception.',
            str_contains($msg, 'cookie') && str_contains($msg, 'HttpOnly') => 'Cookies without the HttpOnly flag are accessible via JavaScript and vulnerable to theft via XSS attacks.',
            default => 'This finding indicates a potential misconfiguration or outdated component visible in server responses. Adjust headers or patch components as needed.',
        };
    }
    

    // Function to generate AI comment using OpenAI API
    public function aiComment(Request $request)
    {
        $prompt = "You are an educational cybersecurity assistant from BRACANA SVET. A student is reviewing a scan finding:\n\n"
            . "Title: {$request->title}\n"
            . "Severity: {$request->severity}\n"
            . "Explanation: {$request->explanation}\n"
            . "Recommendation: {$request->recommendation}\n\n"
            . "Give a friendly, short explanation in plain English to help the student understand what’s happening and what they should do to fix it.";
    
        try {
            Log::info('AI Comment Prompt:', ['prompt' => $prompt]);
    
            $client = \OpenAI::factory()
                ->withApiKey(env('OPENAI_API_KEY'))
                ->make();
            Log::info('OpenAI Client:', ['client' => $client]);
    
            usleep(500000); // Wait for 500 milliseconds (0.5 seconds)
            $response = $client->chat()->create([
                'model' => 'gpt-4',
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);
            usleep(500000); // Wait for 500 milliseconds (0.5 seconds)
            Log::info('AI Response:', ['response' => $response]);
    
            return response()->json([
                'message' => $response['choices'][0]['message']['content']
            ]);
            
        } catch (\Throwable $e) {
            Log::error('AI Comment Error:', ['error' => $e->getMessage()]);
            return back()->with('aiComment', 'Sorry, AI is temporarily unavailable.');
        }
    }

    protected function customSemgrepExplanation(array $item): ?string
    {
        $msg = strtolower($item['message'] ?? '');

        return match (true) {
            str_contains($msg, 'csrf') => 'Cross-Site Request Forgery protection is missing. Consider using a CSRF token.',
            str_contains($msg, 'hard-coded credential') => 'Sensitive credentials should not be hard-coded. Use environment variables.',
            str_contains($msg, 'child_process') => 'Dynamic process execution can lead to command injection. Sanitize user input.',
            default => null,
        };
    }

}
