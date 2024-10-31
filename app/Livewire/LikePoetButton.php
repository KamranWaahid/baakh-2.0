<?php

namespace App\Livewire;

use App\Models\Poets;
use App\Models\UserLikes;
use Livewire\Component;

class LikePoetButton extends Component
{
    public $poet;
    public $isLiked = false;
    public $user;
    
    public function mount(Poets $poet) {
        $this->poet = $poet;

        /** @var \App\Models\User $user */
        $user = auth()->user();
        if($user) {
            $this->user = $user;
            $this->isLiked = $user->likes()->where('likeable_id', $this->poet->id)
                ->where('likeable_type', Poets::class)
                ->exists();
        }
        
    }


    public function toggleLike()
    {
        $like = $this->user->likes()->where('likeable_id', $this->poet->id)
            ->where('likeable_type', Poets::class)
            ->first();

        if ($like) {
            $like->delete();
            $this->isLiked = false;
        } else {
            // If it doesn't exist, create a new like
            UserLikes::create([
                'user_id' => auth()->id(),
                'likeable_id' => $this->poet->id,
                'likeable_type' => Poets::class,
            ]);
            $this->isLiked = true;
        }
    }

    public function render()
    {
        return view('livewire.like-poet-button');
    }
}
