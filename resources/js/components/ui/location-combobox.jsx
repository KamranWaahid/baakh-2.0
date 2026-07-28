import * as React from "react"
import { Check, ChevronsUpDown } from "lucide-react"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command"
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover"

export function LocationCombobox({
    options = [],
    value,
    onChange,
    selectedLabel = null,
    placeholder = "Select location...",
    emptyMessage = "No location found.",
}) {
    const [open, setOpen] = React.useState(false)

    const selected = React.useMemo(() => {
        if (value === null || value === undefined || value === "") {
            return null
        }
        return options.find((option) => String(option.id) === String(value)) ?? null
    }, [options, value])

    // Prefer live option name, then server-provided label — never show bare IDs.
    const displayLabel = selected?.name || selectedLabel || null
    const hasValue = value !== null && value !== undefined && value !== ""

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between font-normal"
                >
                    <span className={cn("truncate", !displayLabel && "text-muted-foreground")}>
                        {displayLabel || (hasValue ? "Loading location…" : placeholder)}
                    </span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                <Command>
                    <CommandInput placeholder="Search location..." />
                    <CommandList>
                        <CommandEmpty>{emptyMessage}</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => {
                                const label = option.name || `City #${option.id}`
                                return (
                                    <CommandItem
                                        key={option.id}
                                        value={`${label} ${option.id}`}
                                        keywords={[String(label), String(option.id)]}
                                        onSelect={() => {
                                            onChange(
                                                String(option.id) === String(value)
                                                    ? ""
                                                    : String(option.id)
                                            )
                                            setOpen(false)
                                        }}
                                    >
                                        <Check
                                            className={cn(
                                                "mr-2 h-4 w-4",
                                                String(value) === String(option.id)
                                                    ? "opacity-100"
                                                    : "opacity-0"
                                            )}
                                        />
                                        {label}
                                    </CommandItem>
                                )
                            })}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    )
}
