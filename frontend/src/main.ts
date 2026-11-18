import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { AppComponent } from './app/app';

console.log('Iniciando Angular...');

bootstrapApplication(AppComponent, appConfig)
  .catch((err) => {
    console.error('ERRO NO BOOTSTRAP:', err);
    document.body.innerHTML = '<h1 style="color:red; text-align:center; margin-top:50px;">Erro no Bootstrap! Verifique o F12.</h1>';
  });
