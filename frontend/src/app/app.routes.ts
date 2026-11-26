import { Routes } from '@angular/router';
import { LoginComponent } from './login/login';
import { DashboardComponent } from './dashboard/dashboard';
import { PainelComponent } from './painel/painel';
import { CarFormComponent } from './car-form/car-form';
import { CadastrosComponent } from './cadastros/cadastros';
import { SettingsComponent } from './settings/settings';
import { RegisterComponent } from './register/register';
import { AuthGuard } from './guards/auth.guard';

export const routes: Routes = [
  { path: '', component: LoginComponent },
  { path: 'register', component: RegisterComponent },
  { path: 'dashboard', component: PainelComponent, canActivate: [AuthGuard] },
  { path: 'veiculos', component: DashboardComponent, canActivate: [AuthGuard] },
  { path: 'cadastrar', component: CarFormComponent, canActivate: [AuthGuard] },
  { path: 'cadastros', pathMatch: 'full', redirectTo: 'cadastros/marcas' },
  { path: 'cadastros/:secao', component: CadastrosComponent, canActivate: [AuthGuard] },
  { path: 'settings', component: SettingsComponent, canActivate: [AuthGuard] }
];
