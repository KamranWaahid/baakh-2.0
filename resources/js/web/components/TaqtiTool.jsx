import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Info, Volume2, BookmarkPlus, AlertCircle, CheckCircle, ChevronDown, RotateCcw } from 'lucide-react';
import { Separator } from '@/components/ui/separator';


const TaqtiTool = ({ lang = 'sd' }) => {
    const isRtl = lang === 'sd';
    const [script, setScript] = useState('perso'); // 'perso' | 'dev'
    const [method, setMethod] = useState('arooz'); // 'arooz' (Arkan) | 'chhand' (Matra)
    const [inputText, setInputText] = useState('');
    const [result, setResult] = useState(null);

    // Mock Scansion Logic
    const handleScan = () => {
        // Simulating delay
        setTimeout(() => {
            setResult({
                status: 'success', // 'success' | 'warning' | 'error'
                meter: {
                    name_arooz: 'بحر هزج مثمن سالم',
                    name_chhand: 'Ghazal Meter (24 Matras)',
                    pattern_arooz: 'مفاعيلن مفاعيلن مفاعيلن مفاعيلن',
                    pattern_chhand: '1222 1222 1222 1222'
                },
                lines: [
                    {
                        original: "پنهنجي شاعري هتي لکو",
                        scanned: [
                            { word: "پنهنجي", syllables: [{ text: "پن", weight: 2, type: 'long' }, { text: "هن", weight: 2, type: 'long' }, { text: "جي", weight: 2, type: 'long' }] },
                            { word: "شاعري", syllables: [{ text: "شا", weight: 2, type: 'long' }, { text: "ع", weight: 1, type: 'short' }, { text: "ري", weight: 2, type: 'long' }] },
                            { word: "هتي", syllables: [{ text: "ه", weight: 1, type: 'short' }, { text: "تي", weight: 2, type: 'long' }] },
                            { word: "لکو", syllables: [{ text: "ل", weight: 1, type: 'short' }, { text: "کو", weight: 2, type: 'long' }] },
                        ],
                        issues: []
                    }
                ]
            });
        }, 800);
    };

    return (
        <div className="w-full max-w-[1080px] mx-auto py-8 px-4 md:px-8 bg-white min-h-[80vh]">

            {/* Header Area */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 className={`text-2xl font-bold text-gray-900 flex items-center gap-2 ${isRtl ? 'font-arabic' : ''}`}>
                        <span className="text-3xl">🪶</span>
                        {isRtl ? 'شاعر جي ورڪ بينچ' : "Poet's Workbench"}
                    </h1>
                    <p className="text-gray-500 text-sm mt-1">
                        {isRtl ? 'بحر، وزن ۽ تقطيع جو جديد اوزار' : 'Advanced tool for Meter, Weight, and Scansion'}
                    </p>
                </div>

                <div className="flex items-center gap-4 bg-gray-50 p-2 rounded-lg border border-gray-100">
                    <div className="flex items-center gap-2 px-2">
                        <span className={`text-sm font-medium ${method === 'chhand' ? 'text-gray-900' : 'text-gray-400'}`}>
                            {isRtl ? 'ڇند وديا' : 'Chhand Widya'}
                        </span>
                        <Switch
                            checked={method === 'arooz'}
                            onCheckedChange={(c) => setMethod(c ? 'arooz' : 'chhand')}
                            className="data-[state=checked]:bg-black"
                        />
                        <span className={`text-sm font-medium ${method === 'arooz' ? 'text-gray-900' : 'text-gray-400'}`}>
                            {isRtl ? 'علم عروض' : 'Ilm Arooz'}
                        </span>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {/* Left: Input Area */}
                <div className="lg:col-span-3">
                    <Card className="border-gray-200 shadow-sm overflow-hidden">
                        <div className="bg-gray-50/50 border-b px-4 py-3 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Tabs value={script} onValueChange={setScript} className="h-8">
                                    <TabsList className="h-8 bg-gray-100/80">
                                        <TabsTrigger value="perso" className="text-xs h-6 px-3">سنڌي (Perso)</TabsTrigger>
                                        <TabsTrigger value="dev" className="text-xs h-6 px-3">सिंधी (Dev)</TabsTrigger>
                                    </TabsList>
                                </Tabs>
                                <div className="text-xs text-gray-400 flex items-center gap-1 cursor-help" title="Long vowels: aa, ii, uu">
                                    <Info className="h-4 w-4" />
                                </div>
                            </div>
                            <Button variant="ghost" size="sm" className="text-xs text-gray-500 hover:text-black gap-1" onClick={() => setInputText('')}>
                                <RotateCcw className="h-3 w-3" /> Clear
                            </Button>
                        </div>

                        <div className="p-0">
                            <textarea
                                value={inputText}
                                onChange={(e) => setInputText(e.target.value)}
                                dir={script === 'perso' ? 'rtl' : 'ltr'}
                                className={`w-full min-h-[160px] p-6 text-2xl border-none focus:ring-0 resize-none leading-loose ${script === 'perso' ? 'font-arabic' : 'font-sans'}`}
                                placeholder={script === 'perso' ? 'پنهنجي شاعري هتي لکو...' : 'Apni shayari hity likho...'}
                            />
                        </div>

                        <div className="p-4 border-t bg-white flex justify-end">
                            <Button onClick={handleScan} size="lg" className="bg-black hover:bg-gray-800 text-white min-w-[140px]">
                                {isRtl ? 'وزن چيڪ ڪريو' : 'Check Vazn'}
                            </Button>
                        </div>
                    </Card>
                </div>

                {/* Results Section */}
                {result && (
                    <div className="lg:col-span-3 space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                        {/* Meter Badge */}
                        <div className="flex items-center justify-between bg-black text-white rounded-xl p-6 shadow-xl">
                            <div className="flex items-center gap-4">
                                <div className="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center text-white backdrop-blur-sm">
                                    <CheckCircle className="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 className={`text-xl font-bold ${isRtl ? 'font-arabic' : ''}`}>
                                        {method === 'arooz' ? result.meter.name_arooz : result.meter.name_chhand}
                                    </h3>
                                    <p className={`text-white/70 font-mono mt-1 ${isRtl ? 'font-arabic' : ''}`}>
                                        {method === 'arooz' ? result.meter.pattern_arooz : result.meter.pattern_chhand}
                                    </p>
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" className="h-9 bg-transparent border-white/20 text-white hover:bg-white hover:text-black transition-all">
                                    <Volume2 className="h-4 w-4 mr-2" /> Play Beat
                                </Button>
                                <Button variant="outline" size="sm" className="h-9 bg-white text-black hover:bg-gray-200 border-white transition-all">
                                    <BookmarkPlus className="h-4 w-4 mr-2" /> Save
                                </Button>
                            </div>
                        </div>

                        {/* Scansion Board */}
                        <div className="space-y-4">
                            {result.lines.map((line, lIdx) => (
                                <Card key={lIdx} className="border border-gray-100 shadow-sm rounded-xl overflow-hidden group/card hover:border-black/50 transition-colors">
                                    <CardContent className="p-6 md:p-10 bg-white">
                                        <div className="flex flex-wrap gap-x-12 gap-y-8 items-start justify-end flex-row-reverse">
                                            {line.scanned.map((wordObj, wIdx) => (
                                                <div key={wIdx} className="flex flex-col items-center group relative min-w-[60px]">

                                                    {/* Syllables Container */}
                                                    <div className="flex gap-0.5 mb-4">
                                                        {wordObj.syllables.map((syl, sIdx) => (
                                                            <div key={sIdx} className="flex flex-col items-center gap-1.5">
                                                                <div
                                                                    className={`
                                                                        w-8 h-10 flex items-end justify-center pb-2 rounded-t-sm text-[10px] font-bold font-mono transition-all duration-300
                                                                        ${syl.type === 'long'
                                                                            ? 'bg-black text-white'
                                                                            : 'bg-gray-100 text-gray-500'}
                                                                    `}
                                                                >
                                                                    {method === 'chhand' ? syl.weight : (syl.type === 'long' ? '-' : 'v')}
                                                                </div>
                                                                <span className={`text-lg text-gray-500 font-medium ${isRtl ? 'font-arabic' : ''} h-8 flex items-center`}>
                                                                    {syl.text}
                                                                </span>
                                                            </div>
                                                        ))}
                                                    </div>

                                                    {/* Full Word */}
                                                    <div className={`text-2xl font-bold text-gray-900 border-t-2 border-gray-100 w-full text-center pt-2 group-hover:border-black transition-colors ${isRtl ? 'font-arabic' : ''}`}>
                                                        {wordObj.word}
                                                    </div>

                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>

                                    <div className="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-between items-center text-xs font-mono text-gray-500 uppercase tracking-widest">
                                        <span>Analysis • Line {lIdx + 1}</span>
                                        <div className="flex gap-4">
                                            <span className="flex items-center gap-1.5"><div className="w-2 h-2 bg-black rounded-full"></div> Long</span>
                                            <span className="flex items-center gap-1.5"><div className="w-2 h-2 bg-gray-300 rounded-full"></div> Short</span>
                                        </div>
                                    </div>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default TaqtiTool;
