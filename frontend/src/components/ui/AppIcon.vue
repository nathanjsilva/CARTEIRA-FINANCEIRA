<script setup>
defineProps({
  name:        { type: String, required: true },
  size:        { type: [Number, String], default: 20 },
  strokeWidth: { type: [Number, String], default: 1.75 },
})

const icons = {
  wallet: [
    'M21 12V7H5a2 2 0 0 1 0-4h14v4',
    'M3 5v14a2 2 0 0 0 2 2h16v-5',
    'M18 12a2 2 0 0 0 0 4h4v-4z',
  ],
  home: [
    'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
    'M9 22V12h6v10',
  ],
  list: [
    'M8 6h13', 'M8 12h13', 'M8 18h13',
    'M3 6h.01', 'M3 12h.01', 'M3 18h.01',
  ],
  'log-out': [
    'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4',
    'M16 17l5-5-5-5',
    'M21 12H9',
  ],
  user: [
    'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2',
    'M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z',
  ],
  plus:  ['M12 5v14', 'M5 12h14'],
  minus: ['M5 12h14'],
  'arrow-right':  ['M5 12h14', 'M12 5l7 7-7 7'],
  'arrow-left':   ['M19 12H5', 'M12 19l-7-7 7-7'],
  'arrow-down-circle': [
    'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z',
    'M8 12l4 4 4-4',
    'M12 8v8',
  ],
  'arrow-up-circle': [
    'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z',
    'M16 12l-4-4-4 4',
    'M12 16V8',
  ],
  'arrows-left-right': [
    'M7 16l-4-4 4-4',
    'M3 12h18',
    'M17 8l4 4-4 4',
  ],
  'refresh-ccw': [
    'M1 4v6h6',
    'M3.51 15a9 9 0 1 0 .49-4.65',
  ],
  check:         ['M20 6L9 17l-5-5'],
  x:             ['M18 6L6 18', 'M6 6l12 12'],
  'alert-circle': [
    'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z',
    'M12 8v4',
    'M12 16h.01',
  ],
  info: [
    'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z',
    'M12 16v-4',
    'M12 8h.01',
  ],
  eye: [
    'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z',
    'M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
  ],
  'eye-off': [
    'M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94',
    'M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19',
    'M1 1l22 22',
  ],
  'chevron-left':  ['M15 18l-6-6 6-6'],
  'chevron-right': ['M9 18l6-6-6-6'],
  'chevron-down':  ['M6 9l6 6 6-6'],
  'chevron-up':    ['M18 15l-6-6-6 6'],
  search: ['M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0'],
  filter: [
    'M22 3H2l8 9.46V19l4 2v-8.54L22 3z',
  ],
  'trending-up':   ['M23 6l-9.5 9.5-5-5L1 18', 'M17 6h6v6'],
  'trending-down': ['M23 18l-9.5-9.5-5 5L1 6',  'M17 18h6v-6'],
  calendar: [
    'M19 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z',
    'M16 2v4M8 2v4M3 10h18',
  ],
  copy: [
    'M20 9H11a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2z',
    'M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 0 2 2v1',
  ],
}
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    :stroke-width="strokeWidth"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path v-for="(d, i) in (icons[name] ?? [])" :key="i" :d="d" />
  </svg>
</template>
