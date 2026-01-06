import { router, usePage } from '@inertiajs/react';

/**
 * Pagination component for admin tables
 * Uses Inertia partial reloads to navigate without showing query parameters in URL
 * 
 * @param {Object} pagination - Pagination data from Laravel paginator (passed via Inertia)
 * @param {Function} onPageChange - Optional callback when page changes
 * @param {Boolean} preserveScroll - Preserve scroll position on navigation
 */
export default function Pagination({ pagination, onPageChange, preserveScroll = false }) {
  const { translations } = usePage().props;
  const paginationTranslations = translations?.pagination || {};

  // Extract pagination metadata
  const currentPage = pagination?.current_page;
  const lastPage = pagination?.last_page;
  const total = pagination?.total;
  const prevPageUrl = pagination?.prev_page_url;
  const nextPageUrl = pagination?.next_page_url;

  // Don't show pagination if there's only one page or no data
  if (!pagination || !lastPage || lastPage <= 1) {
    return null;
  }

  /**
   * Navigate to a specific page using Inertia partial reload
   * Avoids showing ?page=X in the URL by using replace mode
   */
  function goToPage(page) {
    if (page < 1 || page > lastPage || page === currentPage) {
      return;
    }

    if (onPageChange) {
      // Use custom callback if provided (for pages with filters)
      onPageChange(page);
    } else {
      // Default behavior: navigate preserving current query params
      const currentParams = new URLSearchParams(window.location.search);
      currentParams.set('page', page);
      
      router.get(
        window.location.pathname + '?' + currentParams.toString(),
        {},
        {
          preserveState: true,
          preserveScroll,
          replace: true,
          only: ['users', 'logs'], // Only reload these props
        }
      );
    }
  }

  /**
   * Generate array of page numbers to display
   * Shows first, last, current and surrounding pages with ellipsis
   */
  function getPageNumbers() {
    const current = currentPage;
    const last = lastPage;
    const delta = 2; // Number of pages to show on each side of current

    const pages = [];
    const rangeStart = Math.max(2, current - delta);
    const rangeEnd = Math.min(last - 1, current + delta);

    // Always show first page
    pages.push(1);

    // Add ellipsis if there's a gap after first page
    if (rangeStart > 2) {
      pages.push('...');
    }

    // Add pages around current page
    for (let i = rangeStart; i <= rangeEnd; i++) {
      pages.push(i);
    }

    // Add ellipsis if there's a gap before last page
    if (rangeEnd < last - 1) {
      pages.push('...');
    }

    // Always show last page (if more than one page)
    if (last > 1) {
      pages.push(last);
    }

    return pages;
  }

  const pageNumbers = getPageNumbers();

  return (
    <div className="mt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
      {/* Page info */}
      <div className="text-sm text-gray-600">
        Page {currentPage} / {lastPage} — {total} {total > 1 ? 'résultats' : 'résultat'}
      </div>

      {/* Pagination controls */}
      <div className="flex items-center gap-1">
        {/* Previous button */}
        <button
          onClick={() => goToPage(currentPage - 1)}
          disabled={!prevPageUrl}
          className="px-3 py-2 text-sm rounded-md border border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
          dangerouslySetInnerHTML={{ __html: paginationTranslations.previous || '&laquo; Précédent' }}
        />

        {/* Page numbers */}
        <div className="hidden sm:flex items-center gap-1">
          {pageNumbers.map((page, index) => {
            if (page === '...') {
              return (
                <span key={`ellipsis-${index}`} className="px-2 text-gray-500">
                  ...
                </span>
              );
            }

            const isActive = page === currentPage;
            return (
              <button
                key={page}
                onClick={() => goToPage(page)}
                disabled={isActive}
                className={`min-w-[2.5rem] px-3 py-2 text-sm rounded-md border transition-colors ${
                  isActive
                    ? 'bg-gray-800 text-white border-gray-800 cursor-default'
                    : 'border-gray-300 hover:bg-gray-50'
                }`}
              >
                {page}
              </button>
            );
          })}
        </div>

        {/* Next button */}
        <button
          onClick={() => goToPage(currentPage + 1)}
          disabled={!nextPageUrl}
          className="px-3 py-2 text-sm rounded-md border border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
          dangerouslySetInnerHTML={{ __html: paginationTranslations.next || 'Suivant &raquo;' }}
        />
      </div>
    </div>
  );
}
