<?php /* static page using Vue via CDN */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>PrintFlow — Board</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,sans-serif;margin:16px}
    .board{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}
    .col{background:#f7f7f7;border:1px solid #e3e3e3;border-radius:8px;padding:8px}
    .col h3{margin:4px 0 8px;font-size:16px}
    .card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:8px;margin-bottom:8px}
    .muted{color:#666;font-size:12px}
    .btn{padding:4px 8px;border:1px solid #ccc;border-radius:6px;background:#f5f5f5;cursor:pointer}
    .btn + .btn{margin-left:6px}
  </style>
</head>
<body>
  <h1>PrintFlow — Board</h1>

  <div id="app">
    <div v-if="loading">Loading…</div>
    <div v-else class="board">
      <div class="col" v-for="s in statuses" :key="s">
        <h3>{{ labels[s] }} ({{ grouped[s] ? grouped[s].length : 0 }})</h3>

        <div class="card" v-for="o in grouped[s]" :key="o.id">
          <div><strong>#{{ o.id }}</strong> — {{ o.client }}</div>
          <div>{{ o.object_name }}</div>
          <div class="muted">{{ o.material }} · {{ o.est_weight_g }}g · ¥{{ Number(o.price_jpy).toLocaleString() }}</div>
          <div class="muted">Created: {{ o.created_at }}</div>
          <div style="margin-top:6px">
            <button class="btn" v-if="o.status !== 'completed'" @click="advance(o.id)">Advance</button>
            <button class="btn" @click="remove(o.id)">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Vue 3 CDN -->
  <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
  <script>
    const { createApp } = Vue;

    createApp({
      data() {
        return {
          loading: true,
          orders: [],
          statuses: ['requested','design','printing','qa','completed'],
          labels: { requested:'Requested', design:'Design', printing:'Printing', qa:'QA', completed:'Completed' }
        };
      },
      computed: {
        grouped() {
          const g = { requested: [], design: [], printing: [], qa: [], completed: [] };
          for (const o of this.orders) {
            if (g[o.status]) g[o.status].push(o);
          }
          return g;
        }
      },
      methods: {
        async load() {
          try {
            this.loading = true;
            const res = await fetch('/api/orders.php', { headers: { 'Accept': 'application/json' }});
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            this.orders = Array.isArray(json.orders) ? json.orders : [];
          } catch (err) {
            console.error('Failed to load orders:', err);
            alert('Could not load orders. See console.');
          } finally {
            this.loading = false;
          }
        }, // 👈 keep this comma!

        async advance(id) {
          try {
            const form = new FormData();
            form.append('id', id);
            const res = await fetch('/api/advance.php', { method: 'POST', body: form });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            if (json.order) {
              const idx = this.orders.findIndex(o => o.id == id);
              if (idx !== -1) this.orders[idx] = json.order;
              // Optional: ensure perfect sync
              // await this.load();
            } else {
              alert(json.error || 'Error advancing order');
            }
          } catch (err) {
            console.error('Advance failed:', err);
            alert('Advance failed. See console.');
          }
        }, // 👈 and this comma!

        async remove(id) {
          try {
            const form = new FormData();
            form.append('id', id);
            const res = await fetch('/api/delete.php', { method: 'POST', body: form });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            if (json.ok) {
              this.orders = this.orders.filter(o => o.id != id);
              // Optional: ensure perfect sync
              // await this.load();
            } else {
              alert(json.error || 'Delete failed');
            }
          } catch (err) {
            console.error('Delete failed:', err);
            alert('Delete failed. See console.');
          }
        }
      },
      mounted() { this.load(); }
    }).mount('#app');
  </script>
</body>
</html>
