function replaceWordsHeySudhar(inputText, replacementMap) {
    // Split the input text into individual lines
    var lines = inputText.split('\n');
  
    // Iterate over each line
    var replacedLines = lines.map(function(line) {
      // Split the line into individual words
      var words = line.split(' ');
  
      // Iterate over each word
      var replacedWords = words.map(function(word) {
        // Check if the word exists in the replacement map
        if (replacementMap.hasOwnProperty(word)) {
          // Replace the word with its corresponding English word
          return replacementMap[word];
        } else {
          // Handle words with punctuation marks
          var punctuation = getWordPunctuationHeySudhar(word);
          var cleanedWord = removePunctuationHeySudhar(word, punctuation);

          // Handle Words with Derivatives
  
          // Check if the cleaned word exists in the replacement map
          if (replacementMap.hasOwnProperty(cleanedWord)) {
            // Replace the cleaned word with its corresponding English word
            var replacedWord = replacementMap[cleanedWord];
  
            // Add back the punctuation marks to the replaced word
            replacedWord = addPunctuationHeySudhar(replacedWord, punctuation);
  
            return replacedWord;
          }
        }
  
        // If the word is not found in the replacement map, return the original word
        return word;
      });
  
      // Join the replaced words back into a single line
      var replacedLine = replacedWords.join(' ');
  
      return replacedLine;
    });
  
    // Join the replaced lines back into a single string
    var replacedText = replacedLines.join('\n');
  
    return replacedText;
  }
  
  // Function to get the punctuation marks of a word
  function getWordPunctuationHeySudhar(word) {
    var punctuation = '';
    
    // Define the Sindhi punctuation marks
    var sindhiPunctuation = ['،', '؛', '.', '”', '“', '،', '،', '!', '?', '؟'];
    
    // Check if the word starts or ends with punctuation marks
     var punctuationMatch = word.match(new RegExp('^[\\w\\s' + sindhiPunctuation.join('') + ']|^[\\w\\s' + sindhiPunctuation.join('') + ']+|[\\w\\s' + sindhiPunctuation.join('') + ']$|[\\w\\s' + sindhiPunctuation.join('') + ']+$', 'g'));

    if (punctuationMatch) {
      punctuation = punctuationMatch[0].trim();
    }
    
    return punctuation;
  }

   
  // Function to remove punctuation marks from a word
  function removePunctuationHeySudhar(word, punctuation) {
    // Define the Sindhi punctuation marks and their English counterparts
    var sindhiPunctuationMap  = {
      '،': ',',
      '؛': ';',
      '.': '.',
      '”': '"',
      '“': '"',
      '،': ',',
    };
  
    // Remove the punctuation marks from the word
    var cleanedWord = word.replace(new RegExp('[' + punctuation + ']', 'g'), '');
  
    // Replace the Sindhi punctuation marks with their English counterparts
    for (var i = 0; i < punctuation.length; i++) {
      var punctuationMark = punctuation[i];
      if (sindhiPunctuationMap.hasOwnProperty(punctuationMark)) {
        cleanedWord = cleanedWord.replace(new RegExp('\\' + punctuationMark, 'g'), sindhiPunctuationMap[punctuationMark]);
      }
    }
  
    return cleanedWord;
  }
  
  // Function to add punctuation marks to a word
  function addPunctuationHeySudhar(word, punctuation) {
    return word + punctuation;
  }

  // function to get ARAAB of a word
  function getWordVowels(word)
  {
    var vowels = '';
    
    // Define the Sindhi vowels marks
    var sindhiVowels = ['َ', 'ِ', 'ُ', 'ً', 'ٖ', 'ّ', 'ۡ', 'ٌ', 'ٍ'];
    
    // Check if the word starts or ends with punctuation marks
     var vowelsMatch = word.match(new RegExp('^[\\w\\s' + sindhiVowels.join('') + ']|^[\\w\\s' + sindhiVowels.join('') + ']+|[\\w\\s' + sindhiVowels.join('') + ']$|[\\w\\s' + sindhiVowels.join('') + ']+$', 'g'));

    if (vowelsMatch) {
      vowels = vowelsMatch[0].trim();
    }
    return vowels;
  }

  // Function to remove punctuation marks from a word
  function removeWordVowels(word, punctuation) {
    // Define the Sindhi punctuation marks and their English counterparts
    var sindhiPunctuationMap  = {
      '،': ',',
      '؛': ';',
      '.': '.',
      '”': '"',
      '“': '"',
      '،': ',',
    };
  
    // Remove the punctuation marks from the word
    var cleanedWord = word.replace(new RegExp('[' + punctuation + ']', 'g'), '');
  
    // Replace the Sindhi punctuation marks with their English counterparts
    for (var i = 0; i < punctuation.length; i++) {
      var punctuationMark = punctuation[i];
      if (sindhiPunctuationMap.hasOwnProperty(punctuationMark)) {
        cleanedWord = cleanedWord.replace(new RegExp('\\' + punctuationMark, 'g'), sindhiPunctuationMap[punctuationMark]);
      }
    }
  
    return cleanedWord;
  }
 
  
  // Load the Sindhi words and their English replacements
  async function loadReplacementMapHeySudhar() {
    try {
      const response = await fetch(hesudhar_dict_file, {cache: 'no-cache'});
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
          var originalWord = parts[0].trim();
          var hesudharWord = parts[1].trim();
          replacementMap[originalWord] = hesudharWord;
        }
      });
  
      return replacementMap;
    } catch (error) {
      console.error('Error:', error);
      throw error;
    }
  }
  
  // Replace the input text with Sindhi words converted to English
  async function heySudhar(button) {
    // get input fields names
    var sindhiField = button.getAttribute("data-sindhi-field");

    // get button to show and remove current
    var buttonToShow = button.getAttribute("data-button-show")
 
    var inputText = document.getElementById(sindhiField).value;
    var replacementMap = await loadReplacementMapHeySudhar();

    var replacedText = replaceWordsHeySudhar(inputText, replacementMap);
    document.getElementById(sindhiField).value = replacedText;
    document.getElementById(buttonToShow).style.display = "inline-block"
    button.style.display = "none"
    
  }
  



  