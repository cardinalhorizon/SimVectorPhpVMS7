<script setup>
const props = defineProps({
  currentPage: {
    type: Number,
    required: true
  },
  lastPage: {
    type: Number,
    required: true
  },
  total: {
    type: Number,
    required: true
  },
  perPage: {
    type: Number,
    default: 15
  },
  from: {
    type: Number,
    default: 0
  },
  to: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits(['page-change']);

const handlePageChange = (page) => {
  if (page >= 1 && page <= props.lastPage && page !== props.currentPage) {
    emit('page-change', page);
  }
};

const getPages = () => {
  const pages = [];
  const maxPages = 7;

  if (props.lastPage <= maxPages) {
    for (let i = 1; i <= props.lastPage; i++) {
      pages.push(i);
    }
  } else {
    if (props.currentPage <= 4) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i);
      }
      pages.push('...');
      pages.push(props.lastPage);
    } else if (props.currentPage >= props.lastPage - 3) {
      pages.push(1);
      pages.push('...');
      for (let i = props.lastPage - 4; i <= props.lastPage; i++) {
        pages.push(i);
      }
    } else {
      pages.push(1);
      pages.push('...');
      for (let i = props.currentPage - 1; i <= props.currentPage + 1; i++) {
        pages.push(i);
      }
      pages.push('...');
      pages.push(props.lastPage);
    }
  }

  return pages;
};
</script>

<template>
  <div class="row">
    <div class="col-xl-9 col-lg-12 text-center">
      <nav v-if="lastPage > 1" aria-label="Page navigation">
        <ul class="pagination justify-content-center">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <a
              class="page-link"
              href="#"
              @click.prevent="handlePageChange(currentPage - 1)"
              aria-label="Previous"
            >
              <span aria-hidden="true">&laquo;</span>
            </a>
          </li>

          <template v-for="(page, index) in getPages()" :key="index">
            <li
              v-if="page === '...'"
              class="page-item disabled"
            >
              <span class="page-link">...</span>
            </li>
            <li
              v-else
              class="page-item"
              :class="{ active: page === currentPage }"
            >
              <a
                class="page-link"
                href="#"
                @click.prevent="handlePageChange(page)"
              >
                {{ page }}
              </a>
            </li>
          </template>

          <li class="page-item" :class="{ disabled: currentPage === lastPage }">
            <a
              class="page-link"
              href="#"
              @click.prevent="handlePageChange(currentPage + 1)"
              aria-label="Next"
            >
              <span aria-hidden="true">&raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

