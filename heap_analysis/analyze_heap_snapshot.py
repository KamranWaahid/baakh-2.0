import sys
import json
import os
from heap_parser import load_snapshot

def analyze(file_path):
    snapshot = load_snapshot(file_path)
    if not snapshot:
        return {"error": "File not found"}

    results = {
        "summary": {
            "total_nodes": snapshot.get_nodes_count(),
            "total_size": sum(snapshot.nodes[i] for i in range(snapshot.node_fields.index('self_size'), len(snapshot.nodes), snapshot.node_size)),
            "strings_count": len(snapshot.strings)
        },
        "findings": []
    }

    # 1. Detached DOM nodes
    detached_nodes = []
    for i in range(snapshot.get_nodes_count()):
        node = snapshot.get_node(i)
        if node['type'] == 'synthetic': continue
        name = node['name']
        
        # Filter out V8 internal builtins that contain 'Detached'
        if '(builtin code)' in name or '(native)' in name: continue
        
        # Look for actual detached DOM nodes
        # They usually look like 'Detached HTMLDivElement' or 'Detached Text'
        if name.startswith('Detached '):
            detached_nodes.append(node)
        elif node['type'] == 'object' and ('HTML' in name or 'Element' in name) and name.startswith('Detached'):
            detached_nodes.append(node)

    if detached_nodes:
        results["findings"].append({
            "id": "detached_dom",
            "title": "Detached DOM nodes (memory leaks)",
            "severity": "high",
            "items": [f"{n['name']} ({n['self_size']} bytes)" for n in detached_nodes[:10]],
            "count": len(detached_nodes),
            "action": "Ensure event listeners are removed and DOM references are cleared when components unmount."
        })

    # 2. Large strings / data URLs
    large_strings = []
    for s in snapshot.strings:
        if len(s) > 10000: # > 10KB
            large_strings.append(s[:100] + "...")
    
    if large_strings:
        results["findings"].append({
            "id": "large_strings",
            "title": "Large base64 JSON / Strings",
            "severity": "medium",
            "items": large_strings[:5],
            "count": len(large_strings),
            "action": "Avoid embedding large JSON in data URLs; load via fetch or lazy-load when needed."
        })

    # 3. Closures
    closures_count = sum(1 for i in range(snapshot.get_nodes_count()) if snapshot.get_node(i)['type'] == 'closure')
    if closures_count > 1000:
        results["findings"].append({
            "id": "closures",
            "title": "Many closures",
            "severity": "low",
            "count": closures_count,
            "action": "Review closures in loops and event handlers; avoid capturing large objects."
        })

    return results

if __name__ == "__main__":
    snapshot_path = sys.argv[1] if len(sys.argv) > 1 else None
    if not snapshot_path:
        # Fallback to default Download path or specific file if available
        # For now, we expect the path as argument
        print(json.dumps({"error": "No file path provided"}))
        sys.exit(1)
    
    analysis_results = analyze(snapshot_path)
    print(json.dumps(analysis_results, indent=2))
