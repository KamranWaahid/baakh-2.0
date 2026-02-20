import json
import os

class HeapSnapshot:
    def __init__(self, data):
        self.snapshot = data['snapshot']
        self.nodes = data['nodes']
        self.edges = data['edges']
        self.strings = data['strings']
        
        self.node_fields = self.snapshot['meta']['node_fields']
        self.node_types = self.snapshot['meta']['node_types'][self.node_fields.index('type')]
        self.node_size = len(self.node_fields)
        
        self.edge_fields = self.snapshot['meta']['edge_fields']
        self.edge_types = self.snapshot['meta']['edge_types'][self.edge_fields.index('type')]
        self.edge_size = len(self.edge_fields)

    def get_node(self, index):
        start = index * self.node_size
        node = {}
        for i, field in enumerate(self.node_fields):
            val = self.nodes[start + i]
            if field == 'type':
                node[field] = self.node_types[val]
            elif field == 'name':
                node[field] = self.strings[val]
            else:
                node[field] = val
        return node

    def get_nodes_count(self):
        return len(self.nodes) // self.node_size

def load_snapshot(file_path):
    if not os.path.exists(file_path):
        return None
    with open(file_path, 'r') as f:
        data = json.load(f)
    return HeapSnapshot(data)
