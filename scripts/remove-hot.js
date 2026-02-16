/**
 * Elimina public/hot antes del build.
 * Cuando existe public/hot, Laravel carga assets desde el dev server en vez del build.
 * Si el dev server no está corriendo, los estilos y JS fallan.
 */
const fs = require('fs');
const path = require('path');
const hotPath = path.join(__dirname, '..', 'public', 'hot');
try {
  if (fs.existsSync(hotPath)) {
    fs.unlinkSync(hotPath);
    console.log('✓ Eliminado public/hot para usar build compilado');
  }
} catch (e) {
  // Ignorar si no existe o no se puede eliminar
}
