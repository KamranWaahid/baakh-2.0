<?php

namespace App\Livewire;

use App\Models\Poetry;
use App\Models\UserComments;
use Livewire\Component;

class PoetryComments extends Component
{
    public $poetry;
    public $comments = []; // Store comments
    public $comment; // New comment input
    public $stars = 5; // New comment input

    public $reply_id;
    public $commentId;

    public $user; // loggedin user


    protected $rules = [
        'comment' => 'required|min:5',
    ];


    public function mount(Poetry $poetry)
    {
        $this->poetry = $poetry;
        $this->comments = UserComments::where('poetry_id', $this->poetry)->where('status', 'approved')->get();
    }

    public function submitComment()
    {
        $this->validate();

        $reply_id = $this->reply_id ?? null;
        
        if (UserComments::where('book_id', $this->poetry->id)->where('user_id', $this->user->id)->exists()) {
            session()->flash('error', 'توھان ھِن ڪتاب تي فقط ھڪ ڀيرو ئي راءِ ڏئي سگهو ٿا');
            return;
        }

        UserComments::create([
            'poetry_id' => $this->poetry->id,
            // 'parent_id' => $reply_id,
            'user_id' => $this->user->id(),
            'stars' => $this->stars,
            'comment' => $this->comment,
            'status' => 'approved', // Set initial status to pending
        ]);

        $this->comment = ''; // Clear the input
        $this->comments = UserComments::where('poetry_id', $this->poetry->id)->where('status', 'approved')->get(); // Refresh comments
        // $this->emit('commentAdded'); // Optional: Emit an event if needed
    }


    public function render()
    {
        return view('livewire.poetry-comments');
    }
}
