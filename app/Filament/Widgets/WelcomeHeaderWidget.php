<?php

declare(strict_types=1);
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WelcomeHeaderWidget extends Widget
{
    protected static string $view = 'filament.widgets.welcome-header-widget';

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();

        return [
            'user' => $user,
            'greeting' => $this->getGreeting(),
            'quote' => $this->getQuote(),
        ];
    }

    private function getGreeting(): string
    {
        $hour = now()->hour;
        if ($hour < 12) return 'Chào buổi sáng';
        if ($hour < 18) return 'Chào buổi chiều';
        return 'Chào buổi tối';
    }

    private function getQuote(): string
    {
        $quotes = [
            "Thành công không phải là đích đến, đó là một hành trình.",
            "Hãy làm việc chăm chỉ trong im lặng, để thành công lên tiếng.",
            "Cơ hội không tự đến, bạn tạo ra chúng.",
            "Khách hàng là thượng đế, nhưng sự chân thành là chìa khóa.",
            "Mỗi cuộc gọi là một cơ hội mới.",
        ];

        return $quotes[array_rand($quotes)];
    }
}
