<?php

namespace App\Livewire;

use App\Models\Poetry;
use App\Models\User;
use App\Models\UserLikes;
use Livewire\Component;

class LikePoetryButton extends Component
{
    public $poetry;
    public $isLiked = false;
    public $user;
    
    public function mount(Poetry $poetry) {
        $this->poetry = $poetry;

        /** @var \App\Models\User $user */
        $user = auth()->user();
        if($user) {
            $this->user = $user;
            $this->isLiked = $user->likes()->where('likeable_id', $this->poetry->id)
                ->where('likeable_type', Poetry::class)
                ->exists();
        }
        
    }


    public function toggleLike()
    {
        $like = $this->user->likes()->where('likeable_id', $this->poetry->id)
            ->where('likeable_type', Poetry::class)
            ->first();

        if ($like) {
            $like->delete();
            $this->isLiked = false;
        } else {
            // If it doesn't exist, create a new like
            UserLikes::create([
                'user_id' => auth()->id(),
                'likeable_id' => $this->poetry->id,
                'likeable_type' => Poetry::class,
            ]);
            $this->isLiked = true;
        }
    }


    public function render()
    {
        return view('livewire.like-poetry-button');
    }
}
