import { TFilters } from '../types';

export const resolveFilters = (filters: TFilters) => {
  const resolvedFilters: Record<string, string> = {};

  filters?.forEach((filter) => {
    const { name, operator, value } = filter;

    if (name && operator && value !== undefined && value !== null) {
      const key = `filter[${name}][${operator}]`;

      if (value && Array.isArray(value)) {
        resolvedFilters[key] = value.join(',');
      } else if (typeof value === 'number' || typeof value === 'boolean') {
        resolvedFilters[key] = value.toString();
      } else if (typeof value === 'string') {
        resolvedFilters[key] = value;
      }
    }
  });

  return resolvedFilters;
};
