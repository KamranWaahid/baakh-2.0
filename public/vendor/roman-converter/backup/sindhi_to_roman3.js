function replaceWords(inputText, replacementMap) {
  // Split the input text into individual words
  var words = inputText.split(' ');

  // Iterate over each word
  var replacedWords = words.map(function(word) {
    // Check if the word exists in the replacement map
    if (replacementMap.hasOwnProperty(word)) {
      // Replace the word with its corresponding English word
      return replacementMap[word];
    } else {
      // Handle words with punctuation marks
      var punctuation = getWordPunctuation(word);
      var cleanedWord = removePunctuation(word, punctuation);
      
      // Check if the cleaned word exists in the replacement map
      if (replacementMap.hasOwnProperty(cleanedWord)) {
        // Replace the cleaned word with its corresponding English word
        var replacedWord = replacementMap[cleanedWord];
         
         // Add back the punctuation marks to the replaced word
         replacedWord = addPunctuation(replacedWord, punctuation);
         
        
        return replacedWord;
      }
    }
    
    // If the word is not found in the replacement map, return the original word
    return word;
  });

  // Join the replaced words back into a single string
  var replacedText = replacedWords.join(' ');

  return replacedText;
}

// Function to get the punctuation marks of a word
function getWordPunctuation(word) {
  var punctuation = '';
  
    // Define the Sindhi punctuation marks
    var sindhiPunctuation = ['\u060C', '\x0D', '\x0A', '\u201D', '\x0D', '\x0A', '\u201C', '\x0D', '\x0A', '\u2019', '\x0D', '\x0A', '\u2018', '\x0D', '\x0A', '\u0964'];

    // Check if the word starts or ends with punctuation marks
    var punctuationMatch = word.match(new RegExp('^[\\w\\s' + sindhiPunctuation.join('') + ']|^[\\w\\s' + sindhiPunctuation.join('') + ']+|[\\w\\s' + sindhiPunctuation.join('') + ']$|[\\w\\s' + sindhiPunctuation.join('') + ']+$', 'g'));
  
    
    if (punctuationMatch) {
      punctuation = punctuationMatch[0];
    }
    
    return punctuation;
}

// Function to remove punctuation marks from a word
function removePunctuation(word, punctuation) {
  // Define the Sindhi punctuation marks and their English counterparts
  var sindhiPunctuationMap  = {
    '،': ',', // Sindhi comma to English comma
    '؛': ';',
    '.': '.',
    '”': '"',
    '“': '"',
    '،':',',
    // Add more mappings as needed
  };

  // Remove the punctuation marks from the word
  var cleanedWord = word.replace(new RegExp('^[\\w\\s' + punctuation + ']|^[\\w\\s' + punctuation + ']+|[\\w\\s' + punctuation + ']$|[\\w\\s' + punctuation + ']+$', 'g'), '');

  // Replace the Sindhi punctuation marks with their English counterparts
  for (var i = 0; i < punctuation.length; i++) {
    var punctuationMark = punctuation[i];
    if (sindhiPunctuationMap.hasOwnProperty(punctuationMark)) {
      cleanedWord = cleanedWord.replace(new RegExp('\\' + punctuationMark, 'g'), sindhiPunctuationMap[punctuationMark]);
    }
  }

  return cleanedWord;

}

// Function to replace specific Sindhi punctuation marks with their English counterparts within a word
function replacePunctuation(word, punctuation) {
  // Define the punctuation mapping
  var sindhiPunctuationMap  = {
    '،': ',', // Sindhi comma to English comma
    '؛': ';',
    '.': '.',
    '”': '"',
    '“': '"',
    '،':',',
    // Add more mappings as needed
  };

  // Iterate over each character in the word
  var replacedWord = '';
  for (var i = 0; i < word.length; i++) {
    var character = word[i];
    // Check if the character is a punctuation mark and needs mapping
    if (sindhiPunctuationMap.hasOwnProperty(character)) {
      replacedWord += sindhiPunctuationMap[character];
    } else {
      replacedWord += character;
    }
  }
  
  return replacedWord;
}


// Function to add punctuation marks to a word
function addPunctuation(word, punctuation) {
  return word + punctuation;
}

  
  // Load the Sindhi words and their English replacements
  async function loadReplacementMap() {
    try {
      const response = await fetch(myFilesUrl + 'all_words.txt');
      if (!response.ok) {
        throw new Error('Failed to fetch the Sindhi words file');
      }
      const fileContent = await response.text();
  
      // Parse the Sindhi words file content into a replacement map object
      const lines = fileContent.split('\n');
      var replacementMap = {};
      lines.forEach(function(line) {
        var parts = line.split(':');
        if (parts.length === 2) {
          var sindhiWord = parts[0].trim();
          var englishWord = parts[1].trim();
          replacementMap[sindhiWord] = englishWord;
        }
      });
  
      return replacementMap;
    } catch (error) {
      console.error('Error:', error);
      throw error;
    }
  }
  
  // Replace the input text with Sindhi words converted to English
  async function convertText() {
    var inputText = document.getElementById('couplet_text').value;
    var replacementMap = await loadReplacementMap();
    var replacedText = replaceWords(inputText, replacementMap);
    document.getElementById('roman_txt').value = replacedText;
  }
  