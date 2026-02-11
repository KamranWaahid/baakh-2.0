import React from 'react';
import { Button } from '@/components/ui/button';
import { Construction, ArrowLeft, Timer, Hammer, Sparkles } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

const UnderDevelopment = ({ title = "Not Implemented" }) => {
    const navigate = useNavigate();

    return (
        <div className="flex-1 flex flex-col items-center justify-center p-8 text-center space-y-8 animate-in fade-in duration-700">
            <div className="relative">
                <div className="absolute -inset-4 bg-primary/10 rounded-full blur-2xl animate-pulse" />
                <div className="relative bg-background border-2 border-primary/20 p-6 rounded-3xl shadow-2xl">
                    <Construction className="h-16 w-16 text-primary animate-bounce-slow" />
                </div>
                <Sparkles className="absolute -top-2 -right-2 h-6 w-6 text-amber-400 animate-pulse" />
            </div>

            <div className="max-w-md space-y-4">
                <h1 className="text-4xl font-extrabold tracking-tight lg:text-5xl bg-gradient-to-br from-foreground to-muted-foreground bg-clip-text text-transparent">
                    {title}
                </h1>
                <p className="text-xl text-muted-foreground">
                    This module is currently not implemented in the live admin system.
                </p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full max-w-sm">
                <div className="flex items-center gap-3 p-4 rounded-xl bg-muted/50 border border-muted">
                    <Timer className="h-5 w-5 text-primary" />
                    <div className="text-left text-sm">
                        <p className="font-semibold text-foreground">Estimated Launch</p>
                        <p className="text-muted-foreground">Q2 2026</p>
                    </div>
                </div>
                <div className="flex items-center gap-3 p-4 rounded-xl bg-muted/50 border border-muted">
                    <Hammer className="h-5 w-5 text-primary" />
                    <div className="text-left text-sm">
                        <p className="font-semibold text-foreground">Status</p>
                        <p className="text-muted-foreground">In Progress</p>
                    </div>
                </div>
            </div>

            <Button
                variant="outline"
                size="lg"
                onClick={() => navigate('/admin')}
                className="group border-primary/20 hover:border-primary/50 transition-all duration-300"
            >
                <ArrowLeft className="mr-2 h-4 w-4 group-hover:-translate-x-1 transition-transform" />
                Back to Dashboard
            </Button>
        </div>
    );
};

export default UnderDevelopment;
