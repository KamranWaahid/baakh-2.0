var sindhiWords = [];
var englishWords = [];

function loadSindhiWords() {
  var xhr = new XMLHttpRequest();
  xhr.open("GET", "sindhi_words.txt", true);
  xhr.onload = function() {
    if (xhr.status === 200) {
      var sindhiWordsText = xhr.responseText;
      sindhiWords = sindhiWordsText.split("\n");
    }
  };
  xhr.send();
}

function loadEnglishWords() {
  var xhr = new XMLHttpRequest();
  xhr.open("GET", "roman_words.txt", true);
  xhr.onload = function() {
    if (xhr.status === 200) {
      var englishWordsText = xhr.responseText;
      englishWords = englishWordsText.split("\n");
    }
  };
  xhr.send();
}

function translate(){
    
    var inputText = document.getElementById("couplet_text").value;
    var responseText = "";
    var words  = inputText.split(/\s+/);
    var newLines = [];

    for (var i = 0; i < words .length; i++) {
      var word = words [i];
      
      if (sindhiWords.indexOf(word) !== -1) {
        responseText += englishWords[sindhiWords.indexOf(word)] + " ";
      } else {
        responseText += word + " ";
      }

      if (word === "\n") {
        newLines.push(word);
      }
    }
    for (var i = 0; i < newLines.length; i++) {
        responseText += "<br>";
      }
    document.getElementById("roman_txt").innerHTML = responseText;
  }
  document.getElementById("btn-roman-convert").onclick = translate
  window.onload = function() {
    loadSindhiWords();
    loadEnglishWords();
    
  };

 