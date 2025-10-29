/**
 * Formata um número de telefone brasileiro
 * @param {string} value - Número de telefone sem formatação
 * @returns {string} Telefone formatado (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
 */
export function formatPhone(value) {
  const numbers = value.replace(/\D/g, '')
  if (numbers.length <= 10) {
    return numbers.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3')
  }
  return numbers.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3')
}

/**
 * Formata um CPF brasileiro
 * @param {string} value - CPF sem formatação
 * @returns {string} CPF formatado XXX.XXX.XXX-XX
 */
export function formatCPF(value) {
  const numbers = value.replace(/\D/g, '')
  return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}

/**
 * Formata um CNPJ brasileiro
 * @param {string} value - CNPJ sem formatação
 * @returns {string} CNPJ formatado XX.XXX.XXX/XXXX-XX
 */
export function formatCNPJ(value) {
  const numbers = value.replace(/\D/g, '')
  return numbers.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
}

/**
 * Formata um CEP brasileiro
 * @param {string} value - CEP sem formatação
 * @returns {string} CEP formatado XXXXX-XXX
 */
export function formatCEP(value) {
  const numbers = value.replace(/\D/g, '')
  return numbers.replace(/(\d{5})(\d{3})/, '$1-$2')
}

/**
 * Remove toda formatação de uma string, mantendo apenas números
 * @param {string} value - String com formatação
 * @returns {string} Apenas números
 */
export function onlyNumbers(value) {
  return value.replace(/\D/g, '')
}
