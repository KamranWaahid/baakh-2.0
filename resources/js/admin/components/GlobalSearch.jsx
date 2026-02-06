import React, { useState, useEffect } from 'react';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";
import { Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import api from '../../api/axios';
import { useQuery } from '@tanstack/react-query';

// Simple debounce hook implementation since I don't want to assume external lib
function useDebounceValue(value, delay) {
    const [debouncedValue, setDebouncedValue] = useState(value);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);

    return debouncedValue;
}

export function GlobalSearch({ onSelect, type = 'hesudhar', ...props }) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState("");
    const debouncedSearch = useDebounceValue(search, 300);

    useEffect(() => {
        const down = (e) => {
            if (e.key === "k" && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setOpen((open) => !open);
            }
        };
        document.addEventListener("keydown", down);
        return () => document.removeEventListener("keydown", down);
    }, []);

    const { data, isLoading } = useQuery({
        queryKey: ['search', type, debouncedSearch],
        queryFn: async () => {
            if (!debouncedSearch) return { data: [] };
            // Adjust API endpoint based on type or use a generic search endpoint
            // For now, mapping to hesudhar as per context
            const response = await api.get('/api/admin/hesudhar', {
                params: { search: debouncedSearch, per_page: 5 }
            });
            return response.data;
        },
        enabled: open && debouncedSearch.length > 0,
    });

    return (
        <>
            <Button
                variant="outline"
                className={cn(
                    "relative h-9 w-9 p-0 xl:h-10 xl:w-60 xl:justify-start xl:px-3 xl:py-2",
                    "text-muted-foreground bg-background hover:bg-accent/50",
                    props.className
                )}
                onClick={() => setOpen(true)}
            >
                <Search className="h-4 w-4 xl:mr-2" />
                <span className="hidden xl:inline-flex">Search...</span>
                <kbd className="pointer-events-none absolute right-1.5 top-2 hidden h-6 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium opacity-100 xl:flex">
                    <span className="text-xs">⌘</span>K
                </kbd>
            </Button>
            <CommandDialog open={open} onOpenChange={setOpen}>
                <CommandInput
                    placeholder="Type to search..."
                    value={search}
                    onValueChange={setSearch}
                />
                <CommandList>
                    <CommandEmpty>No results found.</CommandEmpty>
                    {search && !isLoading && data?.data && (
                        <CommandGroup heading="Results">
                            {data.data.map((item) => (
                                <CommandItem
                                    key={item.id}
                                    value={item.word + " " + item.correct} // Ensure search matches
                                    onSelect={() => {
                                        setOpen(false);
                                        if (onSelect) onSelect(item);
                                    }}
                                >
                                    <div className="flex flex-col">
                                        <span className="font-medium">{item.word}</span>
                                        <span className="text-xs text-muted-foreground">{item.correct}</span>
                                    </div>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}
                </CommandList>
            </CommandDialog>
        </>
    );
}
