<?php

namespace App\Livewire;

use App\Models\Couplets;
use App\Models\UserLikes;
use Livewire\Component;

class LikeCoupletButton extends Component
{
    public $couplet;
    public $isLiked = false;
    public $user;
    
    public function mount(Couplets $couplet) {
        $this->couplet = $couplet;

        /** @var \App\Models\User $user */
        $user = auth()->user();
        if($user) {
            $this->user = $user;
            $this->isLiked = $user->likes()->where('likeable_id', $this->couplet->id)
                ->where('likeable_type', Couplets::class)
                ->exists();
        }
        
    }


    public function toggleLike()
    {
        $like = $this->user->likes()->where('likeable_id', $this->couplet->id)
            ->where('likeable_type', Couplets::class)
            ->first();

        if ($like) {
            $like->delete();
            $this->isLiked = false;
        } else {
            // If it doesn't exist, create a new like
            UserLikes::create([
                'user_id' => auth()->id(),
                'likeable_id' => $this->couplet->id,
                'likeable_type' => Couplets::class,
            ]);
            $this->isLiked = true;
        }
    }

    public function render()
    {
        return view('livewire.like-couplet-button');
    }
}
