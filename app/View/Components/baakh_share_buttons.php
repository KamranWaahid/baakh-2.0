<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class baakh_share_buttons extends Component
{
    protected $componentId;
    protected $poetryUrl;
    protected $shareText;

    /**
     * Create a new component instance.
     */
    public function __construct($componentId, $poetryUrl, $shareText)
    {
        $this->shareText = $shareText;
        $this->componentId = $componentId;
        $this->poetryUrl = $poetryUrl;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.baakh_share_buttons');
    }
}
