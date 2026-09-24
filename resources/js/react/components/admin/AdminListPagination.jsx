import { ADMIN_PER_PAGE_OPTIONS } from '../../constants/adminPagination';

/**
 * Admin list footer: rows-per-page (10/25/50/100) + prev/next navigation.
 */
export default function AdminListPagination({
  currentPage = 1,
  lastPage = 1,
  perPage = 25,
  total = 0,
  onPageChange,
  onPerPageChange,
  itemLabel = 'items',
  className = '',
}) {
  if (total === 0 && !onPerPageChange) {
    return null;
  }

  const from = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
  const to = total === 0 ? 0 : Math.min(currentPage * perPage, total);
  const canPrev = currentPage > 1;
  const canNext = currentPage < lastPage;

  return (
    <div
      className={`flex flex-col gap-3 border-t border-gray-200 bg-gray-50/50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 ${className}`}
    >
      <div className="text-sm text-gray-700">
        {total > 0 ? (
          <>
            Showing <span className="font-medium">{from}</span> to{' '}
            <span className="font-medium">{to}</span> of{' '}
            <span className="font-medium">{total}</span> {itemLabel}
          </>
        ) : (
          <span className="text-gray-500">No {itemLabel}</span>
        )}
      </div>

      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
        {onPerPageChange && (
          <div className="flex items-center gap-2">
            <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">
              Rows
            </span>
            <div className="inline-flex rounded-lg border border-gray-200 bg-white p-0.5 shadow-sm">
              {ADMIN_PER_PAGE_OPTIONS.map((size) => (
                <button
                  key={size}
                  type="button"
                  onClick={() => {
                    if (size !== perPage) {
                      onPerPageChange(size);
                    }
                  }}
                  className={`min-w-[2.5rem] rounded-md px-2.5 py-1.5 text-sm font-semibold transition-colors ${
                    perPage === size
                      ? 'bg-purple-600 text-white shadow-sm'
                      : 'text-gray-600 hover:bg-gray-100'
                  }`}
                  aria-pressed={perPage === size}
                  aria-label={`Show ${size} rows per page`}
                >
                  {size}
                </button>
              ))}
            </div>
          </div>
        )}

        {lastPage > 1 && onPageChange && (
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => onPageChange(currentPage - 1)}
              disabled={!canPrev}
              className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 transition-colors"
            >
              Previous
            </button>
            <span className="text-sm text-gray-600 tabular-nums whitespace-nowrap">
              Page {currentPage} / {lastPage}
            </span>
            <button
              type="button"
              onClick={() => onPageChange(currentPage + 1)}
              disabled={!canNext}
              className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 transition-colors"
            >
              Next
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
