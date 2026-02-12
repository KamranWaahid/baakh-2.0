import React from 'react';
import {
    LayoutDashboard,
    Server,
    Database,
    Code,
    Globe,
    Box,
    Cpu,
    Shield,
    Search,
    Zap,
    Layers
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

const TechStackCard = ({ title, icon: Icon, items }) => {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">
                    {title}
                </CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="flex flex-wrap gap-2 mt-2">
                    {items.map((item, index) => (
                        <div key={index} className="flex flex-col gap-1 w-full border-b pb-2 last:border-0 last:pb-0">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">{item.name}</span>
                                {item.version && <Badge variant="secondary" className="text-xs">{item.version}</Badge>}
                            </div>
                            {item.description && <span className="text-xs text-muted-foreground">{item.description}</span>}
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
};

const InformationSystem = () => {
    const frontendStack = [
        { name: 'React', version: 'v19', description: 'User Interface Library' },
        { name: 'Vite', version: 'v4', description: 'Build Tool & Bundler' },
        { name: 'Tailwind CSS', version: 'v3', description: 'Utility-first CSS Framework' },
        { name: 'shadcn/ui', description: 'Re-usable components built with Radix UI and Tailwind' },
        { name: 'Zustand', description: 'Small, fast and scalable bearbones state-management' },
        { name: 'TanStack Query', version: 'v5', description: 'Powerful asynchronous state management' },
        { name: 'React Router', version: 'v6', description: 'Declarative routing' },
        { name: 'Lucide React', description: 'Beautiful & consistent icons' },
    ];

    const backendStack = [
        { name: 'Laravel', version: 'v10', description: 'PHP Framework for Web Artisans' },
        { name: 'PHP', version: 'v8.3', description: 'Server-side scripting language' },
        { name: 'MySQL', description: 'Relational Database Management System' },
        { name: 'Laravel Sanctum', description: 'API Authentication system' },
        { name: 'Laravel Scout', description: 'Full-text search abstraction' },
        { name: 'Meilisearch', description: 'Lightning fast, hyper-relevant search engine' },
        { name: 'Spatie Permission', description: 'Roles and permissions management' },
    ];

    const infrastructureStack = [
        { name: 'Nginx / Apache', description: 'Web Server' },
        { name: 'Composer', description: 'Dependency Manager for PHP' },
        { name: 'npm', description: 'Node Package Manager' },
        { name: 'GitHub Actions', description: 'CI/CD Pipeline for Beta/Main deployment' },
    ];

    return (
        <div className="space-y-6">
            <div className="flex items-center justifying-between">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Information System</h2>
                    <p className="text-muted-foreground">Technical overview of the application stack and architecture.</p>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <TechStackCard
                    title="Frontend Architecture"
                    icon={LayoutDashboard}
                    items={frontendStack}
                />
                <TechStackCard
                    title="Backend Services"
                    icon={Server}
                    items={backendStack}
                />
                <TechStackCard
                    title="Infrastructure & Tools"
                    icon={Cpu}
                    items={infrastructureStack}
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>System Interactions</CardTitle>
                    <CardDescription>High-level data flow and integration points.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4 text-sm text-gray-600">
                        <p>
                            The application follows a decoupled <strong>Client-Server architecture</strong>.
                            The <strong>React Frontend</strong> communicates with the <strong>Laravel Backend</strong> exclusively via RESTful APIs.
                        </p>
                        <ul className="list-disc pl-5 space-y-2">
                            <li>
                                <strong>Authentication:</strong> Handled by Laravel Sanctum using stateless API tokens.
                                Social login (Google) is integrated via Laravel Socialite.
                            </li>
                            <li>
                                <strong>State Management:</strong> Global UI state is managed by Zustand, while server data caching and synchronization are handled by TanStack Query.
                            </li>
                            <li>
                                <strong>Search:</strong> Search queries are processed by Laravel Scout, which offloads the heavy lifting to a Meilisearch instance for sub-millisecond response times.
                            </li>
                            <li>
                                <strong>Assets:</strong> Images and media are managed via Spatie Media Library and optimized using Intervention Image v3.
                            </li>
                        </ul>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default InformationSystem;
